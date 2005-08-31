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
  $view_xid = $_REQUEST["view_xid"];
  $next = $_REQUEST["next"];
  $sql_begin = "SELECT * FROM assessments WHERE id_pool=$id_pool AND (assessment = 'U' or inconsistant='Y') AND xid " ;
  if (($row = sql_get_row($sql_begin . ($next ? ">" : "<") . " $view_xid ORDER BY xid " . ($next ? "" : "DESC") . " LIMIT 0,1", false))
|| ($row = sql_get_row($sql_begin . ($next ? "<" : ">") . " $view_xid ORDER BY xid " . ($next ? "" : "DESC") . " LIMIT 0,1", false))) {
        $rfile = sql_get_row("SELECT * FROM files WHERE type='xml' AND xid <= $row[xid] AND post >= $row[xid]");
        header("Location: $PHP_SELF?id_pool=$id_pool&file=$rfile[name]");
        exit();
    } else {
        header("Location: $base_url/pool.php?id_pool=$id_pool&message=Pool%20completed%20!!!");
        exit();
    }

}

// Retrieve information on the current file & display the current assessments

$file = $_REQUEST["file"];
$directory = dirname($file);
$collection = $_REQUEST["collection"];
$documentns = "urn:xrai:c:$collection";

$row = $xrai_db->getRow("select id,title,parent from $db_files where collection=? AND filename=?",array($collection,$file));
//print "$query";
$title = $row["title"];
$fileid = $row["id"];
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
$localisation[] = array("File $file","$PHP_SELF?id_pool=$id_pool&amp;file=$file&amp;collection=$collection","$title");



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
   if (DB::isError($cursor)) fatal_error("Could not retrieve assessments",$cursor->getUserInfo());

}

?>

<!-- Our own style & js -->
<link rel="stylesheet" href="<?=$base_url?>/css/collections/<?=$collection?>.css" />
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
  id_pool = <?=$id_pool?>;
  write_access = <?=$can_modify ? "true" : "false"?>;
  debug = <?=$do_debug ? "true": "false"?>;
  up_url = "<?=$up_url?>";
  var write_access = <?=($write_access ? "true":"false")?>;
  var xraiatag = "<?=$xraiatag?>";

<? if ($id_pool > 0) { ?>
   var writeAccess = true;
   aversion = <?=$assessments->getVersion()?>;
   var docStatus = "<?=abs($assessments->getDone())?>";
   var oldDocStatus = docStatus;
<? } ?>

</script>
<script language="javascript"  src="<?=$base_url?>/js/article.js"/>
<script language="javascript">
  document.onkeypress = XRai.keypressed;
  window.onbeforeunload = XRai.beforeunload;
</script>

<style>


div#inex *[boxit] { border: 1px solid red !important; }

div#inex [selected] { background: #8f8; }
div#inex *[selected] *[marked] { background: #ff8; }
*[marked] { background: yellow; }
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

*|*[first]:before { background: blue; color: white; content : "{"; padding: 3px; font-size: larger; font-weight: bold; }
*|*[last]:after { background: blue; color: white; content : "}"; padding: 3px;  font-size: larger;  font-weight: bold; }

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

if ($num_keywords > 0) print "<style type='text/css'>\n@namespace url($base_url);\n$style\n</style>\n";


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

<!-- Evaluation panel -->
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

<div id="saving_div" style='visibility: hidden; position: fixed; -moz-opacity: .9; margin: auto; left: 40%; border: 2px outset #FFF; background: #000;'><div><img src="<?=$base_url?>/img/xrai-inex.jpg"/></div><div id="saving_message" style='font-size: small; color: #f00; font-weight: bold; text-align: center;'>BLAHBLAH</div></div>

<!-- End of evaluation panel -->

<? } // end of if (write_access)




// Functions called by the PHP (XML+XSL) file
// ==========================================


print "<div id='inex' support='1' src=\"$base_url/iframe/document.php?collection=$collection&amp;file=$file&amp;directory=$directory\" oncontextmenu=\"return show_nav(event);\" ondblclick='' onclick='XRai.onclick(event)' onmouseout='XRai.onmouseout(event)' onmouseover='XRai.onmouseover(event)' onmousemove='XRai.mousemoved(event)'>\n";
// // print "<h1>$title</h1>\n";

$stack = Array();
$load_errors = 0;

function startElement($parser, $name, $attrs) {
   global $depth, $base_url, $stack, $media_url, $collection, $directory, $documentns, $load_errors;
   print "<$name";
   if ($depth == 0) print " xmlns:xraic=\"$documentns\" xmlns=\"$documentns\"";
   $depth++;
   foreach($attrs as $aname => $value) {
      print " $aname=\"$value\"";
   }

//    if ($name == "art") print " xlink:type=\"simple\" xlink:show=\"embed\"  xlink:actuate=\"onLoad\" xlink:href=\"$media_url/$collection/$directory/" .  strtolower(preg_replace("/\.gif$/",".png",$attrs["file"])) . "\"";
   if (sizeof($stack) > 0)
      if ($stack[sizeof($stack)-1] < 0) { $load_errors++; print " error=\"1\""; }
      else $stack[sizeof($stack)-1]++;
   print ">";

   // FIXME: should be DTD independant!
   if ($name == "art")
      print "<html:img src=\"$media_url/$collection/$directory/" .  strtolower(preg_replace("/\.gif$/",".png",$attrs["file"])) . "\"/>";

   array_push($stack,0);
}

function endElement($parser, $name) {
   global $depth, $stack;
   $depth--;
   array_pop($stack);
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
if (!($fp = fopen("$xml_documents/$collection/$file.xml", "r")))
   die("could not open XML input");


while ($data = fread($fp, 4096)) {
   if (!xml_parse($xml_parser, $data, feof($fp))) {
       die(sprintf("XML error: %s at line %d",
                   xml_error_string(xml_get_error_code($xml_parser)),
                   xml_get_current_line_number($xml_parser)));
   }
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
          <span><img onclick="XRai.save();" id="save" src="<?=$base_url?>/img/filenosave.png" alt="Save" title="Save assessments (shift+s)"/><div class="help_bottom">Save the assessements. <br/><b>Shortcut</b>: hold <code>shift</code> and press <code>s</code></div></span>
   </span>

  <span>
      <span><img src="<?=$base_url?>/img/left.png" title="Go to the previous element to assess (control + left arrow)" alt="&lt;-" onclick="todo_previous()"/><div class="help_bottom">Go to the previous element to assess.<br/><b>Shortcut</b>: <code>control + left arrow</code> keys</div></span>
      <span><img src="<?=$base_url?>/img/up.png" title="Go to the container (control + up arrow)" alt="^" onclick="XRai.goUp()"/><div class="help_bottom">Go to the innermost containing collection. <br/><b>Shortcut</b>: <code>control + up arrow</code></div></span>
      <span><img src="<?=$base_url?>/img/right.png" title="Go to the next element to assess (control + right arrow)" alt="-&gt;" onclick="todo_next()"/><div class="help_bottom">Go to the next element to assess.<br/><b>Shortcut</b>: <code>control + right arrow</code></div></span>
   </span>
   <span>
      <span>
         <img id="supportImg" onclick="XRai.switchSupport()" src="<?=$base_url?>/img/eyes.png" alt="[Support]" title="Show/hide the support elements"/>
      </span>
      <span>
         <img id="switchImg" onclick="XRai.switchMode()" src="<?=$base_url?>/img/mode_highlight.png" alt="Finish" title="Switch between highlighting mode and exhaustivity mode"/>
      </span>
      <span>
         <img id="finishImg" onclick="XRai.onFinishClick()" src="<?=$base_url?>/img/disabled_nok.png" alt="Finish" title="Set this article as assessed."/>
         &#8226;
         <span title="Unkown assessments" id="UnknownA">0</span>
      </span>
   </span>

   <span id="highlight">
          <span><img src="<?=$base_url?>/img/highlight.png" alt="[h]"  title="Highlight" onclick="XRai.highlight()"/><div class="help_bottom">Highlight the selected region<br/><b>Shortcut</b>: press the key <code>h</code></div></span>
          <span><img src="<?=$base_url?>/img/unhighlight.png" alt="[u]"  title="Unhighlight" onclick="XRai.unhighlight()"/><div class="help_bottom">Remove the current highlighting of the selected region<br/><b>Shortcut</b>: press the key <code>u</code></div></span>
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
   <?
   while ($row=&$cursor->fetchRow(DB_FETCHMODE_ASSOC)) {
      ?>load.add("<?=$row[startxpath]?>","<?=$row[endxpath]?>","<?=$row[exhaustivity]?>");<?
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
   </script><?
   if ($do_debug) {
      ?><iframe src="<?=$base_url?>/log.html" id="log" align="middle" onclick="this.visibility='hide'"
  style="visibility: hidden; position: fixed; left: 10%; top: 10%; bottom: 10%; right: 10%; z-index: 1; background: white">
   </iframe><?
   }
}


make_footer();

?>
