<?php

/*

	Displays a volume file


*/

// Params $xpath $file $directory $no_topic

include_once("include/xrai.inc");
include_once("include/assessments.inc");

$PHP_SELF = $_SERVER["PHP_SELF"];
set_time_limit(360); // Time limit = 6 minutes

$in_volume = true;

$path = preg_replace('#^/#','',$_SERVER["PATH_INFO"]);
$basepath = "$base_url/article.php?id_pool=$id_pool&file=$path";
$xsl_params = array("basepath" => $basepath);

$row = sql_get_row("SELECT * FROM $db_files WHERE name='$path'");

$title = $row["title"];
$basepath = $row["name"];
$xslname = $row["xsl"];
$view_xid = $row["xid"];

if ($id_pool) 
  $localisation[] = array("$pool[name]","$base_url/pool.php?id_pool=$id_pool", "Pool for topic $pool[id_pool]" );

$i = sizeof($localisation);
do {
  array_splice($localisation,$i,0,array(array($row["name"],"$base_url/collections/$row[name]?id_pool=$id_pool",$row["title"])));
} while ($row = sql_get_row("SELECT * FROM $db_files WHERE name='$row[parent]'",false));
$up_url = $localisation[sizeof($localisation)-2][1];


// $localisation[] = array("$row[","$PHP_SELF?id_pool=$id_pool");
make_header("$title");
$xslfilename = dirname(__FILE__) . "/xsl/$xslname.xsl";
$xmlfilename = "$xml_documents/$path/index.xrai";
// print "$xmlfilename";

// --- Retrieve assessments ---

if ($id_pool) {
$query  = "SELECT f.name, f.parent, assessment, inconsistant, count(*) n"
	. " FROM files f, $db_assessments a "
	. " WHERE f.parent = '$basepath'"
	. " AND a.xid >= f.xid AND a.xid <= f.post and a.id_pool=$id_pool "
	. " GROUP by assessment, a.inconsistant, f.xid";
$qh = sql_query($query);
// print htmlspecialchars($query) . "<br/>";
while ($row = sql_fetch_array($qh)) {
	$a = $row["inconsistant"] == 'Y' ? "I" : $row["assessment"];
//    print $row["name"] . "<br/>";
	$assessments[$row["name"]][$a] += $row["n"];
// 	if ($row["assessment"] == 'U') $to_assess  += $row["n"];*
	if ($row["assessment"] == 'U' || $row["inconsistant"] == 'Y')
		$todojs .= ($todojs ? "," : "todo = new Array(") . "'$row[name]'";
  $stats[$a] += $row["n"];
}
sql_free($qh);
}




// Process XML file with stylesheet (if not in cache)
// ==================================================


function get_full_path($base,$path) {
  return $base . "/" . $path;
}

function print_assessments($id) {
global $assessments, $id_pool;
  if ($id_pool >0) {
		$a = $assessments[$id];
  		if (is_array($a)) {
			print "<span style='border: 1px dashed #bbbbbb; margin-right: 3pt;'>";
			foreach($a as $k => $v) {
			 print  get_evaluation_img($k,false,false,true,$k == 'I') . " $v ";

      		}
			print "</span>";
		}

	}
}

function begin_subcollection($path) {
  global $PHP_SELF, $id_pool, $basepath;
  $id = get_full_path($basepath, $path);
  print_assessments($id);
   print "<a id=\"$id\" href=\"$PHP_SELF/$path?id_pool=$id_pool\"> ";
}

function end_subcollection() { print "</a>"; }

function begin_document($path) {
  global $PHP_SELF, $base_url, $id_pool, $assessments, $basepath;
  $id = get_full_path($basepath, $path);
  print_assessments($id);
  print "<a id='$id' href=\"$base_url/article?id_pool=$id_pool&amp;file=$id\">";
}

function end_document() { print "</a>"; }




if (!$xslt) print "<div class='error'>No XSLT processor defined !</div>";

print "<h1>" . htmlspecialchars($title) . "</h1>\n";
?>
 <script type='text/javascript'>
 id_pool=<?=$id_pool?>;
 <? if ($todojs) print "$todojs);"; else print "todo = new Array();"; ?>
 </script>
<?

print "<div class='inex'>";
xslt_set_encoding($xslt,"UTF-8");

// Has no cache
//  if (!is_file($xmlfilename)) print "<div>$xmlfilename is not a valid file ?</div>\n";
if (!is_dir("$xml_cache/$path")) {
  
  $result = xslt_process($xslt,$xmlfilename,"$xslfilename")  ;
  	if ($result) {
    print "<div class='warning'>No cache directory was found</div>\n";
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
//   print "\n($xmlfilename to $tmpfile)";
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
<div style="padding: 3pt">
 <span>
       <img id="previous_to_assess" src="<?=$base_url?>/img/fgauche.png" title="Previous assessment (shift + left arrow)" onclick="todo_previous()"/>
       <img src="<?=$base_url?>/img/fhaut.png" title="Go to the container (shift + up arrow)" alt="^" onclick="location='<?=$up_url?>'"/>
       <img id="next_to_assess" title="Next assessment (shift+right arrow)" src="<?=$base_url?>/img/fdroit.png" onclick="todo_next()"/>
 </span>
 <span><?=get_stats_string($stats)?></span>
 </div>
</div>
<script language="javascript"  src="<?=$base_url?>/js/collection.js"/>
<script language="javascript">
  up_url = "<?=$up_url?>";
  view_xid = <?=$view_xid?>;
  document.onkeypress = collection_keypress;
</script>

<? } make_footer(); ?>

