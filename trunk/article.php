<?php
/*

   Article view

   (c) B. Piwowarski, 2003-2005


*/

header("Content-cache: 0");
require_once("include/xrai.inc");
require_once("include/assessments.inc");
set_time_limit(360);


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
$row = $xrai_db->getRow("select title,parent from $db_files where collection=? AND filename=?",array($collection,$file));
//print "$query";
$title = $row["title"];
$xmlfilename = "$xml_documents/$collection/$file.xml";

// Begins output

if ($id_pool) 
  $localisation[] = array("$pool[name]","$base_url/pool.php?id_pool=$id_pool", "Pool for topic $pool[idtopic]" );


$i = sizeof($localisation);
while ($row["parent"] > 0 && $row = &$xrai_db->getRow("SELECT * FROM $db_files WHERE id=?",array($row["parent"])))  {
  if (DB::isError($row)) fatal_error("Database error",$row->getUserInfo());
  array_splice($localisation,$i,0,array(array( ($row["filename"] != "" ? $row["filename"] : $row["collection"]), "$base_url/collections/$row[collection]/$row[filename]?id_pool=$id_pool",$row["title"])));
} 
$up_url = $localisation[sizeof($localisation)-2][1];
$localisation[] = array("File $file","$PHP_SELF?id_pool=$id_pool&amp;file=$file&amp;collection=$collection","$title");



add_icon("img_treeview","$base_url/img/tree.png","Tree view (shift + T)","javascript:void(0)","toggle_treeview()",'<div class="help_top">Displays/hides the panel with the tree view of the XML document, where only tag names appear. In this panel, you can click on any tag name to view it in the main document view.<br/><b>Shortcut</b>: hold <code>shift</code> and press <code>t</code></div>');
add_icon("img_bookmarks","$base_url/img/trombone.png","Bookmarks (shift + B)","javascript:void(0)","toggle_bookmarks()",'<div class="help_top">Displays/hides the panel with the current document bookmarks. In this panel, you can click on any displayed path to view it in the main document view.<br/><b>Shortcut</b>: hold <code>shift</code> and press <code>b</code></div>');

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
<link rel="stylesheet" href="<?=$base_url?>/css/article.css" />
<link rel="stylesheet" id="tags_css" href="<?=$base_url?>/css/tags.css" />

<script language="javascript"  src="<?=$base_url?>/js/article.js"/>
<script language="javascript">
  var baseurl = "<?=$baseurl?>";
  var force_regeneration = <?= $force_update ?>;
  var treeview_url = "<?="$base_url/iframe/article_treeview.php?file=$file"?>";
  var xrains = "<?="$base_url"?>";
  var documentns = "<?="$base_url/$collection"?>";
  var xrai_file = "<?=$file?>";
  var xrai_collection = "<?=$collection?>";
  id_pool = <?=$id_pool?>;
  write_access = <?=$can_modify ? "true" : "false"?>;
  debug = <?=$do_debug ? "true": "false"?>;
  up_url = "<?=$up_url?>";
 document.onkeypress = article_keypress;
  window.onbeforeunload = article_beforeunload;
  var write_access = <?=($write_access ? "true":"false")?>;

<? if ($id_pool > 0) { ?>
   aversion = <?=$assessments->getVersion()?>;
<? } ?>

</script>
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

?>
<style type='text/css'>

*|a[*|a='U']:before { content: url(<?=get_assessment_link("U");?>); }
*|a[*|a='1']:before { content: url(<?=get_assessment_link("1");?>); }
*|a[*|a='2']:before { content: url(<?=get_assessment_link("2");?>); }
*|a[*|a='3']:before { content: url(<?=get_assessment_link("3");?>); }
*|a[missing]:after { content: url(<?=$base_url?>/img/warning.png); }
*|a[nobelow] { background: #DD0; }

*|*[hidden] { display: none; }
*|*[hidden]:before { display: none; }

*|*[name='sel'] { background: yellow; }
*|*[name='relevant'] { background: #FFFFA0; }

@namespace url(<?="$base_url/$collection"?>);
@namespace xrai url(<?="$base_url"?>);
article, atl, ip1, sec, sec1, bdy, p, st, fno, tig, hdr1, abs, fig, h, bibl, vt, bb { display: block; }

sec, sec1 { padding-left: 1em; }
p, bb { padding: 1em 0 0 0; }

tbl  { display: table; }

tbl no:before { content: "Table "; }
tbl no:after { content: " "; }
tbl no { font-weight: bold; }
tbl ti { clear: right; }
tbl ti { display: table-caption; }

tbl bdy { display: table-row-group; }
bdy row { display: table-row; }
bdy entry { display: table-cell; }

it { font-style: italic; }
b { font-weight: bold;}
super { vertical-align: text-top; font-size: smaller; }

fm atl { font-size: xx-large;   }

lit { white-space: pre; }
abs { margin: 5px; border: 1px solid gray; padding: 5px; }
abs:before { content: "Abstract"; font-size: large; }

fn { border: 1px solid black; colour: #444444; display: inline; }
fn p { display: inline; }

bdy, bibl { border-top: 1px solid black; }

st,h { font-size: xx-large; colour: blue; padding: 5px 0 5px 0; }

bb ti { font-style: italic; }

l1 { display: block; padding-left: 2em; list-style: outside disc;}
li { display: list-item; }





/* "<?="$media_url/$collection/$directory"?>/"  attr(file)); } */
</style>
<?


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
  style="visibility: hidden; position: fixed; left: 10%; top: 10%; width: <?=($do_debug ? 90 : 60)?>%; height: <?=($do_debug ? 90 : 30)?>%; z-index: 1; background: white; opacity: 80%">
</iframe>


<!-- Evaluation panel -->
<div id="eval_div"  onclick="hideEval()" onmouseover="window.status='Click to assess the element(s)'" onmouseout="window.status=''">
<div id="eval_path"><div></div></div>
<div style="white-space: nowrap;"><?

  foreach($relevances as $a => $t) {
  print "<img "
    . " src='" . get_assessment_link($a,false) . "'"
    . " alt='$t'"
    . " id=\"assess_$a\" onclick=\"assess(this,'$a',event); return false;\" "
    . " title=\"$t\" "
    . " name=\"assess\" "
    . " value=\"$a\" />";
}

?></div>
<div style="white-space: nowrap;">
<img id="eval_breakup_link" src="<?=$base_url?>/img/down.png" href="javascript:void(0)" title="Assess subpassages" alt="down" onclick="goDown()"/>
<img id="nobelow" src="<?=$base_url?>/img/nobelow.png" alt="No below" href="javascript:void(0)" onclick="assess(this,'nobelow',event)" title="Below is too small to assess"/>
</div>
</div>

<div id="saving_div" style='visibility: hidden; position: fixed; -moz-opacity: .9; margin: auto; left: 40%; border: 2px outset #FFF; background: #000;'><div><img src="<?=$base_url?>/img/xrai-inex.jpg"/></div><div id="saving_message" style='font-size: small; color: #f00; font-weight: bold; text-align: center;'>BLAHBLAH</div></div>
      
<!-- End of evaluation panel -->

<? } // end of if (write_access)




// Functions called by the PHP (XML+XSL) file
// ==========================================


print "<div id='inex' src=\"$base_url/iframe/document.php?collection=$collection&amp;file=$file&amp;directory=$directory\" oncontextmenu=\"return show_nav(event);\" ondblclick='do_dblclick(event)' onclick='do_click(event)' onmousemove='XRai.mousemoved(event)'>\n";
// // print "<h1>$title</h1>\n";

function startElement($parser, $name, $attrs) {
   global $depth, $base_url, $media_url, $collection, $directory;
   print "<$name";
   if ($depth == 0) print " xmlns:xraic=\"$base_url/$collection\" xmlns=\"$base_url/$collection\"";
   $depth++;
   foreach($attrs as $aname => $value) {
      print " $aname=\"$value\"";
   }

//    if ($name == "art") print " xlink:type=\"simple\" xlink:show=\"embed\"  xlink:actuate=\"onLoad\" xlink:href=\"$media_url/$collection/$directory/" .  strtolower(preg_replace("/\.gif$/",".png",$attrs["file"])) . "\"";
   print ">";

   // FIXME: should be DTD independant!
   if ($name == "art")
      print "<html:img src=\"$media_url/$collection/$directory/" .  strtolower(preg_replace("/\.gif$/",".png",$attrs["file"])) . "\"/>";
}

function endElement($parser, $name) {
   $depth--;
   print "</$name>";
}

function cdata($parser, $data) {
  global $keywords, $colours, $in_mathml;
  $data = preg_replace(array("/&/"),array("&amp;"),$data);
  if ($in_mathml == 1 || !$keywords) print $data;
  else print preg_replace($keywords,$colours,$data);
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

if ($id_pool > 0) {
   // Display assessments
   ?><script type="text/javascript">
   var load = new XRaiLoad()
   load.begin();<?
   while ($row=&$cursor->fetchRow(DB_FETCHMODE_ASSOC)) {
      ?>load.add("<?=$row[startxpath]?>","<?=$row[endxpath]?>","<?=$row[exhaustivity]?>");<?
   }
   ?>
   load.end();
   </script><?
}

if ($write_access) {
?>

<div id="s_nav" onclick="this.style.visibility='hidden'">
          <img src="img/left.png" alt="&lt;-" onclick="goto_previous_assessment()"/>
          <img src="img/right.png" alt="-&gt;" onclick="goto_next_assessment()"/>
</div>

<div id="s_div" class="status">
  <div>
  <span>
          <span><img onclick="save_assessments();" id="save" src="<?=$base_url?>/img/filenosave.png" alt="Save" title="Save assessments (shift+s)"/><div class="help_bottom">Save the assessements. <br/><b>Shortcut</b>: hold <code>shift</code> and press <code>s</code></div></span>
          <span><img src="<?=$base_url?>/img/greenled.png" alt="[S]"  title="Assess selected elements (control+g)" onclick="show_eval_selected(event.pageX,event.pageY)"/><div class="help_bottom">Assess the selected elements. Elements can be (de)selected by clicking on the <span class="xml">[tag</span> name while pressing the key <code>control</code>. It is also possible to select all the siblings (which are in the same state: assessed or not assessed) with a double-clic.<br/><b>Shortcut</b>: hold the key <code>shift</code> and press <code>g</code></div></span>
          <span><img src="<?=$base_url?>/img/redled.png" alt="[C]"  title="Clear selection (control+shift+g)" onclick="clear_selected()"/><div class="help_bottom">Clear the current element selection (put the mouse over the green disc for more help on selection).<br/><b>Shortcut</b>: hold the key <code>shift</code> and <code>control</code> and press <code>g</code></div></span>
  </span>
  <span>
<!--           <span><img src="img/fgauche.png" title="previous assessment (shift+left arrow)" alt="&lt;-" onclick="todo_previous()"/><div class="help_bottom">Go to the previous Assessment. <br/><b>Shortcut</b>: hold <code>shift</code> and press the left arrow key</div></span> -->

          <span><img src="img/up.png" title="Go to the container (shift+up arrow)" alt="^" onclick="goUp()"/><div class="help_bottom">Go to the innermost containing collection. <br/><b>Shortcut</b>: <code>u</code> key</div></span>
      
      <span style="display: none;" id="imgMissing">
         <img src="<?=$base_url?>/img/warning.png" alt="Missing assessments" title="Some assessments are missing in this view"/>
      </span>

      <span style="display: none; font-size: small;" id="assessedPassageSpan">
         Within <xrai:a a="U"/>
      </span>
<!--           <span><img src="img/fdroit.png" title="Next assessment (shift+right arrow)" alt="-&gt;" onclick="todo_next()"/><div class="help_bottom">Go to the next Assessment. <br/><b>Shortcut</b>: hold <code>shift</code> and press the right arrow key</div></span> -->
   </span>
   <span>
      <span>
         <img src="<?=$base_url?>/img/ok.png" alt="Finish" title="Set this article as assessed."/>
      </span>
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

<script language="javascript">
<![CDATA[
   var tags_css = null;
    for(var i = 0; i < document.styleSheets.length; i++) {
       if (document.styleSheets[i].id == "tags_css") {
          tags_css = document.styleSheets[i];
          break;
       }
    }
    if (tags_css) tags_css.disabled = true;
]]>
</script>

<?
make_footer();

?>
