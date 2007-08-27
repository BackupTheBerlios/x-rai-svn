<?php
/*
    collections.php
    Display table of contents files (used to navigate within a collection
    but not assessed)
    
    Copyright (C) 2003-2007  Benjamin Piwowarski benjamin@bpiwowar.net

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Library General Public
    License as published by the Free Software Foundation; either
    version 2 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Library General Public License for more details.

    You should have received a copy of the GNU Library General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
*/


// Params $xpath $file $directory $no_topic

require_once("include/xrai.inc");
require_once("include/astatus.inc");
require_once("include/xslt.inc");
// require_once("include/assessments.inc");

$PHP_SELF = $_SERVER["PHP_SELF"];
set_time_limit(360); // Time limit = 6 minutes

preg_match('#^/([^/]*)(?:|/(.*[^\/])/*)$#',$_SERVER["PATH_INFO"], $matches);
$collection = $matches[1];
$path = $matches[2];
if (!$path) $path ="";

$basepath = "$base_url/article.php?id_pool=$id_pool&collection=$collection&file=$path";

// SELECT root.id, anc.collection, anc.filename, ta.status,count(*) FROM toassess ta, files anc, files f, files root WHERE anc.pre>=f.pre AND anc.post <= f.post AND ta.idfile=f.id AND root.id=f.parent GROUP BY root.id, anc.id, anc.filename, anc.collection, ta.status

// Get the xrai file information
$row = &$xrai_db->getRow("SELECT * FROM $db_files WHERE collection=? AND filename=?", array($collection,$path));
// print_r($row);
$title = $row["title"];
$basepath = $row["filename"];
$rootid = $row["id"];
$xslname = "xrai";
$viewxid = $row["pre"];

if ($id_pool)
  $localisation[] = array("$pool[name]","$base_url/pool.php?id_pool=$id_pool", "Pool for topic $pool[idtopic]" );

$i = sizeof($localisation);
do {
  if (DB::isError($row)) fatal_error("Database error",$row->getUserInfo());
  array_splice($localisation,$i,0,array(array( ($row["filename"] != "" ? $row["filename"] : $row["collection"]), "$base_url/collections/$row[collection]" . ($row["filename"] ? "/$row[filename]" : "") . "?id_pool=$id_pool",$row["title"])));
} while ($row["parent"] > 0 && $row = &$xrai_db->getRow("SELECT * FROM $db_files WHERE id=?",array($row["parent"])));
$up_url = $localisation[sizeof($localisation)-2][1];


make_header("$title");
$xslfilename = dirname(__FILE__) . "/xsl/$xslname.xsl";



$aPath =  "/$collection" . ($path != "" ? "/$path" : ""); 
if (is_file("$xrai_documents$aPath.xrai")) {
	$xmlfilename = "$xrai_documents/$aPath.xrai";
	$thebasepath = $_SERVER["SCRIPT_NAME"] . preg_replace("#/[^/]+$#","", $aPath);
	if (preg_match("#/#",$basepath)) $basepath = preg_replace("#/[^/]+$#","",$basepath);
	else $basepath = "";
} else {

	$xmlfilename = "$xrai_documents$aPath/index.xrai";
	$thebasepath = $_SERVER["SCRIPT_NAME"] . $aPath;
}
// print "***$thebasepath***<br/>";

// --- Retrieve assessments ---

// select f.filename, fs.status, fs.hasrelevant, count(*) from files f JOIN filestatus fs ON fs.idfile BETWEEN f.pre AND f.post JOIN files fc ON fc.id = f.id WHERE f.parent = (select id from files fp where fp.filename = '4-11-x') GROUP BY f.filename, fs.status, fs.hasrelevant;


if ($id_pool) {
   // Get information about contained documents and xrai files
    $res = &$xrai_db->query("select f.filename, f.type, fs.inpool, fs.status, fs.hasrelevant, count(*) as count from files f JOIN filestatus fs ON fs.idfile BETWEEN f.pre AND f.post AND fs.idpool=? JOIN files fc ON fc.id = f.id WHERE f.parent = (select id from files fp where fp.id = ?) GROUP BY f.filename, f.type, fs.inpool, fs.status, fs.hasrelevant", array($id_pool,$rootid));
//     print $xrai_db->last_query;
//    $res = &$xrai_db->query("SELECT ta.idpool, anc.type, anc.filename, anc.pre, ta.status, ta.inpool, count(*) AS count FROM $db_files root, $db_files anc, $db_files f, $db_filestatus ta   WHERE anc.collection=root.collection AND anc.collection=f.collection AND anc.parent = root.id AND anc.pre <= f.pre AND anc.post >= f.pre AND ta.idfile = f.id AND root.id=? AND idpool=?   GROUP BY ta.idpool, root.id, anc.type, anc.filename, ta.status, ta.inpool, anc.pre ORDER BY pre ASC",array($rootid,$id_pool));
   if (DB::isError($res)) non_fatal_error("Error while retrieving assessments",$res->getUserInfo());
   else {
      $nbRelevant = $nbNotRelevant = $nbToAssess = 0;
      while ($row = $res->fetchRow()) {
         if ($row["type"] == "xml") {
            $document[$row["filename"]] = array($row["status"], $row["inpool"] == $db_true);
         }

         $s = ($row["status"] == 2 ? 2 : 1) * ($row["inpool"] == $db_true ? 1 : -1);
         $assessments[$row["filename"]][$s] = $row["count"];
         $all_assessments[$s] += $row["count"];
                  
         if ($row["status"] != "2") $nbToAssess += $row["count"];
         else if ($row["hasrelevant"] == $db_true) $nbRelevant += $row["count"];
         else $nbNotRelevant += $row["count"];
            
      }
      $res->free();
      print "<div class='info'><b>Informations about this view</b>: $nbToAssess documents need to be assessed; among the assessed documents, $nbRelevant contain relevant passage(s) and $nbNotRelevant do not.</div>";
   }
		 
// 	print_r($assessed);
}


// Process XML file with stylesheet (if not in cache)
// ==================================================


function get_full_path($base,$path) {
  if ($base) return $base . "/" . $path;
  return $path;
}

function printStatistics(&$assessments) {
   global $base_url;
   $x = $assessments[-1] + $assessments[1];
   if ($x > 0) print "<img title=\"$x not validated document(s)\" style=\"vertical-align: middle;\" src=\"$base_url/img/nok\" alt=\"[assessing]\"/>"; 
   
   $x = $assessments[-2] + $assessments[2];
   if ($x > 0) print "<img title='$x validated document(s)' style=\"vertical-align: middle;\"  src=\"$base_url/img/ok\"  alt=\"[validated]\"/>"; 
}

function print_assessments(&$a) {
global $id_pool, $all_assessments;
  if ($id_pool >0) {
//       print "Assessments: " . print_r($assessments[$id], true ) . " for '$id'. ";
      printStatus($a, $all_assessments);
      printStatistics($a);
      print " ";

   }
}

function xmlspecialchars($s) {
   return preg_replace(array('/\'/'),array('&quot;'),$s);
}

function begin_subcollection($path) {
  global $assessments;
  global $PHP_SELF, $id_pool, $thebasepath;
  $id = get_full_path($basepath, $path);
  $a = &$assessments[$id];
  print_assessments($a);
   print "<a " . ($a && ($a[-1] + $a[1] > 0) ? " name='toAssess'" : ""). " id=\"" . xmlspecialchars($id) . "\" href=\"$thebasepath/$path?id_pool=$id_pool\"> ";
}

function end_subcollection() { print "</a>"; }

function begin_document($path) {
  global $PHP_SELF, $base_url, $id_pool, $assessments, $basepath, $collection, $document;
//   print "$basepath";
   $id = get_full_path($basepath, $path);
  if ($document[$id]) {
     $a = &$document[$id];
     if ($a[1]) print "<span style=\"padding: 2px; border: 1px dashed blue\" title=\"in pool\">";
     
     else print "<span>";

      switch($a[0]) {
         case "0": print "<img style=\"vertical-align: center;\" src=\"$base_url/img/mode_highlight\" title=\"highlighting mode\" alt=\"[highlighting]\"/>"; break;
         case "1": print "<img style=\"vertical-align: center;\" src=\"$base_url/img/nok\" title=\"assessing mode (not validated)\" alt=\"[assessing]\"/>"; break;
         case "2": print "<img style=\"vertical-align: center;\"  src=\"$base_url/img/ok\" title=\"assessing mode (validated)\" alt=\"[validated]\"/>"; break;
      }
      print "</span> ";
  }
  print "<a id='" . xmlspecialchars($id) . "'" . ($a[1] ? " name='toAssess'" : "")
   . " href=\"$base_url/article?collection=$collection&amp;id_pool=$id_pool&amp;file=$id\">";
}

function end_document() { print "</a>"; }



$xslt = get_xslt_processor();
if (!$xslt) print "<div class='error'>No XSLT processor defined !</div>";

print "<h1>" . htmlspecialchars($title) . "</h1>\n";
?>
 <script type='text/javascript'>
 id_pool="<?=$id_pool?>";
 viewxid="<?=$viewxid?>";
 </script>
<?

print "<div class='inex' style='margin-bottom: 3cm;'>";

// Has no cache
if (!is_file($xmlfilename)) print "<div>$xmlfilename is not a valid file ?</div>\n";

if (!$xml_cache || !is_dir("$xml_cache/$path")) {
  if ($xslt_mode) {
      $result = xslt_process($xslt,$xmlfilename,"$xslfilename")  ;
   } else {
      $xslt->importStyleSheet(DOMDocument::load($xslfilename));
      $result = $xslt->transformToXML(DOMDocument::load($xmlfilename));
   }
   if ($result) {
      eval("?>" . $result . "<?");
   } else {
      exit("xslt error: " . xslt_error($xslt));
   }
}

// We do have cache
else {
$phpfilename = "$xml_cache/$path/index.php";
if (!hasCache($xmlfilename,$xslfilename,$phpfilename) || filesize($phpfilename) == 0) {
  print "<div style='color: #888888;'>Processing (and caching) file with stylesheet</div>";
  @unlink($phpfilename);
  flush();
  //print nl2br(htmlentities("$xmlcontent"));

  $tmpfile = "$phpfilename.tmp";
//  print "\n($xmlfilename to $tmpfile)";
  if (!@xslt_process($xslt,$xmlfilename,"$xslfilename",$tmpfile,null,$xsl_params)) {
      @unlink($phpfilename);
      fatal_error("xslt error: " . xslt_error($xslt) . "</div>");
    }

  //  chdir($cwd);
  rename($tmpfile,$phpfilename) || $phpfilename = $tmpfile;
}

include($phpfilename);
}

print "</div>\n";


if ($write_access) {
?>
<div id="s_div" class="status">
  <div>
  <span>
      <span><img src="<?=$base_url?>/img/left.png" title="Go to the container (control + left arrow)" alt="^" onclick="todo_previous()"/><div class="help_bottom">Go to the previous collection or document to assess.<br/><b>Shortcut</b>: <code>u</code> key</div></span>
      <span><img src="<?=$base_url?>/img/up.png" title="Go to the container (control + up arrow)" alt="^" onclick="XRai.goUp()"/><div class="help_bottom">Go to the innermost containing collection. <br/><b>Shortcut</b>: <code>control + up arrow</code></div></span>
      <span><img src="<?=$base_url?>/img/right.png" title="Go to the container (control + right arrow)" alt="->" onclick="todo_next()"/><div class="help_bottom">Go to the NEXTlection or document to assess.<br/><b>Shortcut</b>: <code>control + left arrow</code></div></span>
 </span>
</div>
</div>


<script language="javascript"  src="<?=$base_url?>/js/collection.js"/>
<script language="javascript">
  up_url = "<?=$up_url?>";
  document.onkeypress = collection_keypress;
  var todo = document.getElementsByName("toAssess");
</script>

<? } make_footer(); ?>

