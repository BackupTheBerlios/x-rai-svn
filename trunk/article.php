<?php
/*

   Article view

   (c) B. Piwowarski, 2003-2005


*/

header("Content-cache: 0");
require_once("include/xrai.inc");
require_once("include/assessments.inc");
set_time_limit(360);

$xraiatag = "j";

// ------------------------
// --- Retrieve article ---
// ------------------------


// Redirect to the next article to assess
if ($_REQUEST["view_jump"] == 1) {
  $view_pos = $_REQUEST["viewpos"];
  $next = $_REQUEST["next"]; // 0 for previous, 1 for next
  if ($next != "0" && $next != "1") exit();
  $sql_begin = "SELECT a.* FROM $db_files a, $db_filestatus fs WHERE idfile=id AND idpool=? AND status not in (-2,2)";
  $sql[0] = "$sql_begin AND pre < ? ORDER BY a.pre DESC LIMIT 1";
  $sql[1] = "$sql_begin AND pre > ? ORDER BY a.pre ASC LIMIT 1";

  $row = $xrai_db->getRow($sql[$next], array($id_pool, $view_pos), DB_FETCHMODE_ASSOC);
  if (!$row) $row = $xrai_db->getRow($sql[1-$next], array($id_pool, $view_pos), DB_FETCHMODE_ASSOC);
    if ($row) {
        if (DB::isError($row)) { fatal_error("Database error",$row->getUserInfo()); }
        header("Location: $PHP_SELF?id_pool=$id_pool&collection=$row[collection]&file=$row[filename]");
        exit();
    } else {
        header("Location: $base_url/pool?id_pool=$id_pool&message=Pool%20completed%20!!!");
        exit();
    }

}

// Retrieve information on the current file & display the current assessments

$file = stripcslashes($_REQUEST["file"]);
$directory = dirname($file);
$collection = $_REQUEST["collection"];
$documentns = "urn:xrai:c:$collection";

$row = $xrai_db->getRow("select id,title,parent,pre from $db_files where collection=? AND filename=?",array($collection,$file));
//print "$query";
$title = $row["title"];
$fileid = $row["id"];
$viewpos = $row["pre"];
$xmlfilename = "$xml_documents/$collection/$file.xml";

// Begins output

if ($id_pool)
  $localisation[] = array("$pool[name]","$base_url/pool.php?id_pool=$id_pool", "Pool for topic $pool[idtopic]" );


$i = sizeof($localisation);
while ($row["parent"] > 0 && $row = &$xrai_db->getRow("SELECT * FROM $db_files WHERE id=?",array($row["parent"])))  {
  if (DB::isError($row)) fatal_error("Database error",$row->getUserInfo());
  array_splice($localisation,$i,0,array(array( ($row["filename"] != "" ? $row["filename"] : $row["collection"]), "$base_url/collections/$row[collection]/$row[filename]?id_pool=$id_pool",$row["title"])));
}
$up_url = $localisation[sizeof($localisation)-1][1];
$localisation[] = array("File $file","$PHP_SELF?id_pool=$id_pool&amp;file=". rawurlencode($file) . "&amp;collection=$collection","$title");



// add_icon("img_treeview","$base_url/img/tree.png","Tree view (shift + T)","javascript:void(0)","toggle_treeview()",'<div class="help_top">Displays/hides the panel with the tree view of the XML document, where only tag names appear. In this panel, you can click on any tag name to view it in the main document view.<br/><b>Shortcut</b>: hold <code>shift</code> and press <code>t</code></div>');
// add_icon("img_bookmarks","$base_url/img/trombone.png","Bookmarks (shift + B)","javascript:void(0)","toggle_bookmarks()",'<div class="help_top">Displays/hides the panel with the current document bookmarks. In this panel, you can click on any displayed path to view it in the main document view.<br/><b>Shortcut</b>: hold <code>shift</code> and press <code>b</code></div>');

if ($do_debug) {
   add_icon("img_eyes","$base_url/img/eyes.png","Debug view","javascript:void(0)",'toggle_panel("assessing","img_eyes")','');
   add_icon("img_log","$base_url/img/log.png","Log view","javascript:void(0)",'toggle_panel("log","img_log")','');
}

make_header($title);

$force_update = $_GET["force"];
if (!$force_update) $force_update = 0;
$no_mathml = ($force_update == 2); // 2nd update ==> no mathml
if ($id_pool > 0) {
   $assessments = Assessments::create($id_pool, $collection, $file);
   if (DB::isError($assessments))
      fatal_error("Error retrieving assessments",$assessments->getUserInfo());
   $cursor = &$assessments->getCursor();
   $bep = $assessments->getBEP();
   if (DB::isError($cursor)) fatal_error("Could not retrieve assessments",$cursor->getUserInfo());

}

?>

<!-- Our own style & js -->
<link rel="stylesheet" href="<?=$base_url?>/collection/<?=$collection?>.css" />
<link rel="stylesheet" href="<?=$base_url?>/css/article.css" />
<link rel="stylesheet" id="tags_css" href="<?=$base_url?>/css/tags.css" />

<script language="javascript">
  var max_exhaustivity = 2;
  var baseurl = "<?=$baseurl?>";
  var force_regeneration = <?= $force_update ?>;
  var treeview_url = "<?="$base_url/iframe/article_treeview.php?file=$file"?>";
  var xrains = "<?="$xrains"?>";
  var documentns = "<?=$documentns?>";
  var xrai_file = "<?=$file?>";
  var xrai_collection = "<?=$collection?>";
  debug = <?=$do_debug ? "true": "false"?>;
  up_url = "<?=$up_url?>";
  var write_access = <?=($write_access ? "true":"false")?>;
  var xraiatag = "<?=$xraiatag?>";
  var highlight_only = <?=$highlight_only?"true":"false"?>;
  var goto_url = "<?="$_SERVER[PHP_SELF]?id_pool=$id_pool&amp;view_jump=1&amp;viewpos=$viewpos"?>";
  var docStatus;
  var oldDocStatus;

<? if ($id_pool > 0) { ?>
   var id_pool = <?=$id_pool?>;
   var id_topic = "<?=$id_topic?>";
   var writeAccess = true;
   aversion = <?=$assessments->getVersion()?>;
   docStatus = "<?=abs($assessments->getDone())?>";
   oldDocStatus = docStatus;
<? } ?>

</script>
<script language="javascript"  src="<?=$base_url?>/js/article.js"/>
<script language="javascript"  src="<?=$base_url?>/collection/<?=$collection?>.js"/>
<script language="javascript">
  document.onkeypress = XRai.keypressed;
  window.onbeforeunload = XRai.beforeunload;
</script>

<style>


div#inex *[boxit] { border: 1px solid red !important; }

div#inex [selected] { background: #8f8; }
div#inex *[selected] *[marked] { background: #ff8; }
*[marked] { background: yellow !important; }
*[marked]:before { background: yellow !important; }
*[marked]:after { background: yellow !important; }
*[marked] *[marked] { background: red !important; }
*|*[error='1'] { background: red; }
div#inex[support] *[support='1'] { border: 1px dashed blue; }

@namespace url(<?=$xrains?>);

div#inex[mode="highlight"] <?=$xraiatag?> { display: none; }

<?=$xraiatag?>:before { background: red; color: white; content: "[error]"; }

<?=$xraiatag?>[type="passage"] { background: blue; }

<? if ($do_debug) { ?>
<?=$xraiatag?>[type="in"] { background: #aaF; }
<?=$xraiatag?>[type="container"] { background: #0F0 !important; }
<?=$xraiatag?>[intersection] { border: 2px solid #F00 !important;  }
<? } ?>

/* *|html[xraimode='passages'] <?=$xraiatag?>[type='container'] { display: none; } */


<?=$xraiatag?>[a='-1']:before { background: inherit; content: url(<?=get_assessment_link("-1");?>); }
<?=$xraiatag?>[a='0']:before { background: inherit; content: url(<?=get_assessment_link("0");?>); }
<?=$xraiatag?>[a='1']:before { background: inherit; content: url(<?=get_assessment_link("1");?>); }
<?=$xraiatag?>[a='2']:before { background: inherit; content: url(<?=get_assessment_link("2");?>); }

<?=$xraiatag?>[missing]:after { content: url(<?=$base_url?>/img/warning.png); }
<?=$xraiatag?>[deepmissing]:after { content: url(<?=$base_url?>/img/deepwarning.png); }
<?=$xraiatag?>[missing][deepmissing]:after { content: url(<?=$base_url?>/img/warning.png) url(<?=$base_url?>/img/deepwarning.png); }

* [xrai_BEP][marked] { 
	background: no-repeat top left url(<?=$base_url?>/img/bep.png) yellow !important;
}

* [xrai_BEP] { 
	background: no-repeat top left url(<?=$base_url?>/img/bep.png);
}


</style>
<?


// ---------------------------------
// --- Retrieve keywords colours ---
// ---------------------------------

if ($id_pool > 0) {
  $query = "select colour,keywords,mode from $db_keywords where idpool=?";
  $result = $xrai_db->query($query,array($id_pool));
  $style="";
  $num_keywords = 0;
 while ($row = $result->fetchRow(DB_FETCHMODE_BOTH)) {
  $kws = preg_split("-[\n\r]+-",$row[1]);
    $num_keywords++;
   $mode = $row[2];
   $cssname = "k_${mode}_${num_keywords}";
   $style .= " kw[class='$cssname'] { ";
   switch($mode) {
     case "border": $style .= "border: 1pt solid #$row[0];"; break;
     case "colour": $style .= "color: #$row[0];"; break;
     case "background": $style .= "background: #$row[0];"; break;
   }
   $style .= "padding: 1pt; }\n";
  foreach($kws as $kw) {
    $kw = preg_replace('-(^\s+)|(\s+$)-','',$kw);
    if (!preg_match("-\w-",$kw)) continue;
    $kw= preg_replace("-\s*(.*)\s*-",'$1',$kw);
    $kw= preg_replace("-\s\s+-",' ',$kw);
    $keywords[] = "/(\W|^)($kw)(\W|$)/i";
    $colours[] = '$1<xrai:kw class="' . $cssname . '">$2</xrai:kw>$3';
  }
}
}
if ($num_keywords > 0) print "<style type='text/css'>\n@namespace url($xrains);\n$style\n</style>\n";


// --- Retrieve assessments & elements to assess ---

if ($id_pool> 0) {
/*  $doc_assessments = new Assessments($id_pool,"$file","","");
    // makes inference
    if ($do_debug) $doc_assessments->print_debug();
    $doc_assessments->inference();
    if ($do_debug) $doc_assessments->print_changes();
    $doc_assessments->update_database(false);
    if ($do_debug) $doc_assessments->print_debug();*/
}

// ------------------------
// --- Assessment panel ---
// ------------------------


 if ($write_access) {
//  $statistics = $doc_assessments->get_statistics();

?>
<iframe src="about:blank" id="assessing" name="xrai-assessing" align="middle" onclick="this.visibility='hide'"
  style="visibility: hidden; position: fixed; left: 10%; top: 10%; bottom: 10%; right: 10%; z-index: 1; background: white">
</iframe>
<iframe src="about:blank" id="erasing" style="display: none; position: fixed; top: 10; left: 10"></iframe>

<!-- Evaluation panel -->

<div style="background: white; left: 20px; top: 3em; padding: 1em; border: 1px dotted blue; color: black; position: fixed; display: none; "><div style="font-weight: bold; font-size: larger">Informations</div><p id="infopassagediv">BLAH</p><div style="padding-top: 1em;"><a class="normal"  onclick="this.parentNode.parentNode.style.display='none'">Close</a></div></div>


<div id="eval_div"  onclick="hideEval()" onmouseover="window.status='Click to assess the element(s)'" onmouseout="window.status=''">
<div id="eval_path"><div></div></div>
<div style="white-space: nowrap;"><?

  foreach($relevances as $a => $t) {
  print "<img "
    . " src='$base_url/img/" . $t[0] . "'"
    . " alt='$t[1]'"
    . " id=\"assess_$a\" onclick=\"return XRai.assess(this,'$a',event);\" "
    . " title=\"$t[1]\" "
    . " name=\"assess\" "
    . " value=\"$a\" />";
}

?></div>

<div id="nobelow" style="white-space: nowrap;">
<div style="font-size: small; font-weight: bold;">For children</div>
<img src="<?=$base_url?>/img/nobelow.png" alt="No below" href="javascript:void(0)" onclick="XRai.nobelow(XRai.currentAssessed,-1,false)" title="Assess remaining children elements as too small"/>
<img src="<?=$base_url?>/img/nobelow-f.png" alt="No below" href="javascript:void(0)" onclick="XRai.nobelow(XRai.currentAssessed,-1,true)" title="Assess all children elements as too small"/>
<img src="<?=$base_url?>/img/nonobelow.png" alt="No below" href="javascript:void(0)" onclick="XRai.nobelow(XRai.currentAssessed,0)" title="Unassess children elements previously assessed as '&quot;too small&quot;"/>
</div>
</div>


<!-- End of evaluation panel -->

<? } // end of if (write_access)

if ($id_pool > 0) {
?>
<div id="saving_div" style='visibility: visible; position: fixed; opacity: 0.9; -moz-opacity: .9; margin: auto; left: 40%; border: 2px outset #FFF; background: #000;'><div><img src="<?=$base_url?>/img/xrai-inex.jpg"/></div><div id="saving_message" style='font-size: small; color: #f00; font-weight: bold; text-align: center;'>Loading document...</div></div>
<?
}

// Functions called by the PHP (XML+XSL) file
// ==========================================

@include("collection/$collection.inc");

if (function_exists("collectionStartDocument")) {
	collectionStartDocument($collection,$file,$title);
} 

print "<div id='inex' support='1' src=\"$base_url/iframe/document.php?collection=$collection&amp;file=$file&amp;directory=$directory\" oncontextmenu=\"return show_nav(event);\" ondblclick='' onclick='XRai.onclick(event)' onmouseout='XRai.onmouseout(event)' onmouseover='XRai.onmouseover(event)' onmousemove='XRai.mousemoved(event)'>\n";
// // print "<h1>$title</h1>\n";

$stack = Array();
$load_errors = 0;


function startElement($parser, $name, $attrs) {
   global $depth, $base_url, $stack, $media_url, $collection, $directory, $documentns, $load_errors, $xrains;

   if (function_exists("collectionPreStartElement")) collectionPreStartElement($name, $attrs);

   print "<$name";
   if ($depth == 0) print " xmlns:xraic=\"$documentns\" xmlns:xrai=\"$xrains\" xmlns=\"$documentns\"";
   $depth++;
   foreach($attrs as $aname => $value) {
      print " $aname=\"$value\"";
   }

   if (sizeof($stack) > 0)
      if ($stack[sizeof($stack)-1] < 0) { $load_errors++; print " error=\"1\""; }
      else $stack[sizeof($stack)-1]++;
   print ">";

   if (function_exists("collectionStartElement")) collectionStartElement($name, $attrs);

   array_push($stack,0);
}

function endElement($parser, $name) {
   global $depth, $stack;
   $depth--;
   array_pop($stack);
   if (function_exists("collectionEndElement")) collectionEndElement($name);
   print "</$name>";
}

function cdata($parser, $data) {
  global $keywords, $colours, $stack, $in_mathml, $load_errors;

  if ($stack[sizeof($stack)-1] > 0 && !preg_match('/^\s+$/',$data)) {
    $load_errors++;
    $stack[sizeof($stack)-1] = -1;
    $error = true;
    print "<html:span error=\"1\">";
  }

  $data = preg_replace(array("/&/"),array("&amp;"),$data);
  if ($in_mathml == 1 || !$keywords) print $data;
  else print preg_replace($keywords,$colours,$data);
  if ($error) print "</html:span>";
}

$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "cdata");
xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,false);


if (function_exists("getArticle")) {
  if (!($fp = getArticle("$collection","$file")))
     die("could not open XML input");
} else if (!($fp = fopen("$xml_documents/$collection/$file.xml", "r")))
   die("could not open XML input");


$count = 0;
while ($data = fread($fp, 4096)) {
   $count += strlen($data);
   if (!xml_parse($xml_parser, $data, feof($fp))) {
       die(sprintf("XML error: %s at line %d",
                   xml_error_string(xml_get_error_code($xml_parser)),
                   xml_get_current_line_number($xml_parser)));
   }
}

if ($count == 0) {
   print "<div class='error'>Error: empty document</div>";
}

xml_parser_free($xml_parser);

print "</div>";

if ($load_errors) {
   ?>
   <script language="javascript">
      alert("There <?= $load_errors > 1 ? "are $load_errors errors" : "1 error"?> in the structure of this file; you MUST not assess it!");
      // TODO fordbid editing
   </script>
   <?
}

if ($write_access) {
?>


<div id="s_div" class="status">
  <div>
  <span>
          <span><img onclick="XRai.erase();" id="erase" src="<?=$base_url?>/img/trash.png" alt="Erase all assessments from this view" title="Erase assessments"/><div class="help_bottom">Erase all the assessements from this view.</div></span>

          <span><img onclick="XRai.save();" id="save" src="<?=$base_url?>/img/filenosave.png" alt="Save" title="Save assessments (shortcut: control+s)"/><div class="help_bottom">Save the assessements. <br/><b>Shortcut</b>: hold <code>shift</code> and press <code>s</code></div></span>
   </span>
  <span>
      <span><img src="<?=$base_url?>/img/saveandprevious.png" title="Validate, save and go to the previous view" alt="&lt;&lt;" onclick="XRai.saveAndGo(false)"/><div class="help_bottom">Switch to assessment mode, validate, save and go to the previous article to assess</div></span>
<? if (!$highlight_only) { ?>
      <span><img src="<?=$base_url?>/img/left.png" title="Go to the previous element to assess (1)" alt="&lt;-" onclick="todo_previous(event.shiftKey)"/><div class="help_bottom">Go to the previous element (or to the previous view with shift + click) to assess.<br/><b>Shortcut</b>: <code>1</code> for previous element and <code>9</code> for previous view</div></span>
      <span><img src="<?=$base_url?>/img/up.png" title="Go to the container (2)" alt="^" onclick="XRai.goUp()"/><div class="help_bottom">Go to the innermost containing collection. <br/><b>Shortcut</b>: <code>2</code></div></span>
      <span><img src="<?=$base_url?>/img/right.png" title="Go to the next element to assess (3)" alt="-&gt;" onclick="todo_next(event.shiftKey)"/><div class="help_bottom">Go to the next element (or to the next view with shift + click) to assess.<br/><b>Shortcut</b>: <code>3</code>  and <code>0</code> for next view</div></span>
<? } ?>      <span><img src="<?=$base_url?>/img/saveandnext.png" title="Validate, save and go to the previous view" alt="&gt;&gt;" onclick="XRai.saveAndGo(true)"/><div class="help_bottom">Switch to assessment mode, validate, save and go to the next article to assess</div></span>
   </span>

   <span>
   <? if ($assessments && $assessments->inpool) { ?>
      <span>
         <img id="supportImg" onclick="XRai.switchSupport()" src="<?=$base_url?>/img/eyes.png" alt="[Support]"  title="Show/hide the support elements"/><div class="help_bottom">Support elements are the elements returned by participating systems that were selected during the pooling phase. They are shown in blue dotted boxes.</div>
      </span>
   <? } ?>
<? if (!$highlight_only) { ?>
      <span>
         <img id="switchImg" onclick="XRai.switchMode()" src="<?=$base_url?>/img/mode_highlight.png" alt="Finish" title="Switch between highlighting mode and assessment mode (shortcut: &quot;m&quot;)"/>
      </span>
<? } ?>
      <span>
         <img  onclick="XRai.toggleBEPMode()" src="<?=$base_url?>/img/bep.png" alt="BEP" title="Set the BEP."/>
         <img  onclick="XRai.setBEP(null, true)" src="<?=$base_url?>/img/nobep.png" alt="delete BEP" title="Remove the BEP."/>
	 <img id="finishImg" onclick="XRai.onFinishClick()" src="<?=$base_url?>/img/disabled_nok.png" alt="Finish" title="Set this article as assessed."/>
         <? if (!$highlight_only) { ?>&#8226; <span title="Unkown assessments" id="UnknownA">0</span><?}?>
      </span>
   </span>

   <span id="highlight">
          <span><img src="<?=$base_url?>/img/highlight.png" alt="[h]"  title="Highlight" onclick="XRai.highlight()"/><div class="help_bottom">Highlight the selected region<br/><b>Shortcut</b>: press the key <code>h</code></div></span>
          <span><img src="<?=$base_url?>/img/unhighlight.png" alt="[u]"  title="Unhighlight" onclick="XRai.unhighlight()"/><div class="help_bottom">Remove the current highlighting of the selected region<br/><b>Shortcut</b>: press the key <code>u</code></div></span>
  </span>

   <span id="infopassage">
     <span><img src="<?=$base_url?>/img/report.png" alt="[i]"  title="Informations on the passage" onclick="XRai.localise()"/></span>
   </span>

 </div>
</div>
<?
}

// Add Bookmarks
    if ($_GET["h"]) {
    ?>
   <script language="javascript">

     function bookmarks_loaded() {
       var e;
       <? foreach($_GET["h"] as $path) { ?>
             e = get_xrai_element_by_path('<?=$path?>');
            if (!e) alert('<?=$path?>  does not exist');
       else {
          e.style.background = '#ffffcc';
             add_bookmark(e);
       }
      <? } ?>
    }
    </script>
   <?
}

?>
<iframe id='treeview' class='right_panel'/>
<iframe id='bookmarks' src='<?=$base_url?>/iframe/bookmarks.html' class='right_panel'/>
<div id='navigation' onclick="this.style.visibility='hidden'">
<div id='navigation_path'></div>

<!-- Status bar -->
<table>
<tr><td></td><td><img id="nav_parent" onclick="nav_goto(event)" alt="parent" title="Go to parent" src="img/up.png"/></td><td></td></tr>
<tr><td><img  id="nav_prec" onclick="nav_goto(event)" alt="previous sibling" src="img/left.png"/></td><td><img  id="nav_bookmark" onclick="nav_goto(event)" title="bookmark" alt="Add bookmark" src="img/add_bookmark.png"/></td><td><img id="nav_next" onclick="nav_goto(event)"  alt="Go to the next sibling" src="img/right.png"/></td></tr>
<!--<tr><td></td><td><img id="nav_child" onclick="nav_goto(event)"  alt="parent" src="img/down.png"/></td><td></td></tr>-->
</table>
</div>

<!--<script language="javascript">
<![CDATA[
   var tags_css = null;
    for(var i = 0; i < document.styleSheets.length; i++) {
       if (document.styleSheets[i].id == "tags_css") {
          tags_css = document.styleSheets[i];
          break;
       }
    }
    if (tags_css) tags_css.disabled = true;

    // Create variables for element


]]>
</script>-->

<?
if ($id_pool > 0) {
   // Display assessments
   ?><script type="text/javascript">
   XRai.init();
   var load = new XRaiLoad();
   load.setBEP("<?=$bep?>");
   <?
   while ($row=&$cursor->fetchRow(DB_FETCHMODE_ASSOC)) {
      ?>load.add("<?=$row[startxpath]?>","<?=$row[endxpath]?>","<?=$row[exhaustivity]?>");
<?
   }

   // Add topic elements
   $res = $xrai_db->query("SELECT path FROM $db_topicelementsview WHERE idfile=? and idtopic=?",array($fileid,$id_topic));
   if (DB::isError($res)) print "Message.show(\"warning\",\"Could not retrieve support elements\");\n";
   else {
/*      print "alert(\"$fileid, $id_topic\");";
      print "Message.show(\"notice\",\"Retrieved " . $res->numRows() . " support elements\");\n";*/
      while ($row=&$res->fetchRow(DB_FETCHMODE_ROW)) {
         ?>load.addSupport("<?=$row[0]?>");<?
      }
   }

   ?>
   load.end();
   load = null;
   document.getElementById('saving_div').style.visibility = 'hidden'

   </script><?
   if ($do_debug) {
      ?><iframe src="<?=$base_url?>/log.html" id="log" align="middle" onclick="this.visibility='hide'"
  style="visibility: hidden; position: fixed; left: 10%; top: 10%; bottom: 10%; right: 10%; z-index: 1; background: white">
   </iframe><?
   }
}


make_footer();

?>
