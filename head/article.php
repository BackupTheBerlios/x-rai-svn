<?php

/*

	Show an article
	B. Piwowarski, 2003

*/

header("Content-cache: 0");
require_once("include/xrai.inc");
require_once("include/assessments.inc");
set_time_limit(360);

// ------------------------
// --- Retrieve article ---
// ------------------------

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

$file = $_REQUEST["file"];
$row = sql_get_row("select title,xid,post,parent,xsl from files where name='$file'");
//print "$query";
$title = $row[0];
$current_xid = $did = $row[1]; // document ID
$postid = $row[2]; // post id
$xslfilename = dirname(__FILE__) . "/xsl/" . $row[4] . ".xsl";
$xmlfilename = "$xml_documents/$file.xml";


// Begins output

if ($id_pool) $localisation[] = array("$pool[name]","pool.php?id_pool=$id_pool", "Pool for topic $pool[id_pool]" );


$i = sizeof($localisation);
while ($row = sql_get_row("SELECT * FROM $db_files WHERE name='$row[parent]'",false)) {
  array_splice($localisation,$i,0,array(array($row["name"],"$base_url/collections/$row[name]?id_pool=$id_pool",$row["title"])));
}
$up_url = $localisation[sizeof($localisation)-1][1];

$localisation[] = array("File $file","$PHP_SELF?id_pool=$id_pool&amp;file=$file","$title");

add_icon("img_treeview","$base_url/img/tree.png","Tree view (shift + T)","javascript:void(0)","toggle_treeview()",'<div class="help_top">Displays/hides the panel with the tree view of the XML document, where only tag names appear. In this panel, you can click on any tag name to view it in the main document view.<br/><b>Shortcut</b>: hold <code>shift</code> and press <code>t</code></div>');
add_icon("img_bookmarks","$base_url/img/trombone.png","Bookmarks (shift + B)","javascript:void(0)","toggle_bookmarks()",'<div class="help_top">Displays/hides the panel with the current document bookmarks. In this panel, you can click on any displayed path to view it in the main document view.<br/><b>Shortcut</b>: hold <code>shift</code> and press <code>b</code></div>');

make_header($title);

$force_update = $_GET["force"];
if (!$force_update) $force_update = 0;
$no_mathml = ($force_update == 2); // 2nd update ==> no mathml

?>
<!-- Our own style & js -->
<link rel="stylesheet" href="<?=$base_url?>/css/article.css" />
<link rel="stylesheet" id="tags_css" href="<?=$base_url?>/css/tags.css" />

<script language="javascript"  src="<?=$base_url?>/js/article.js"/>
<script language="javascript">
  var baseurl = "<?=$baseurl?>";
  var root_xid = <?=$did?>;
  var force_regeneration = <?= $force_update ?>;
  var treeview_url = "<?="$base_url/iframe/article_treeview.php?file=$file"?>";
//   var assess_url = "assess.php?id_pool=<?=$id_pool?>&amp;file=<?=$file?>";
  up_url = "<?=$up_url?>";
 document.onkeypress = article_keypress;
  window.onbeforeunload = article_beforeunload;
  var write_access = <?=($write_access ? "true":"false")?>;
</script>
<?


// ---------------------------------
// --- Retrieve keywords colours ---
// ---------------------------------

if ($id_pool > 0) {
  $query = "select color,keywords,mode from $db_keywords where id_pool=$id_pool";
  $result = sql_query($query);
  $style="";
  $num_keywords = 0;
 while ($row = mysql_fetch_row($result)) {
  $kws = preg_split("-[\n\r]+-",$row[1]);
	$num_keywords++;
   $mode = $row[2];
   $cssname = "k_${mode}_${num_keywords}";
	$style .= " span.$cssname { ";
   switch($mode) {
     case "border": $style .= "border: 1pt solid #$row[0];"; break;
     case "color": $style .= "color: #$row[0];"; break;
     case "background": $style .= "background: #$row[0];"; break;
   }
   $style .= "padding: 1pt; }\n";
  foreach($kws as $kw) {
	$kw = preg_replace('-(^\s+)|(\s+$)-','',$kw);
  	if (!preg_match("-\w-",$kw)) continue;
    $kw= preg_replace("-\s*(.*)\s*-",'$1',$kw);
    $kw= preg_replace("-\s\s+-",' ',$kw);
    $keywords[] = "/(\W|^)($kw)(\W|$)/i";
    $colours[] = '$1<span class="' . $cssname . '">$2</span>$3';
  }
}
}

if ($num_keywords > 0) print "<style type='text/css'>\n$style\n</style>\n";

// --- Retrieve assessments & elements to assess ---

if ($id_pool> 0) {
	$doc_assessments = new Assessments($id_pool,"$file","","");
	// makes inference
	if ($do_debug) $doc_assessments->print_debug();
	$doc_assessments->inference();
	if ($do_debug) $doc_assessments->print_changes();
	$doc_assessments->update_database(false);
	if ($do_debug) $doc_assessments->print_debug();
}

// ------------------------
// --- Assessment panel ---
// ------------------------


?>
<script type="text/javascript" language="javascript">
<![CDATA[
sorted_assessments = new Array(<?=sizeof($sorted_assessments); ?>);
<?
foreach($sorted_assessments as $k => $v) {
  print "sorted_assessments[$k] = '$v';\n";
}
?>

assessments = { 'A': 'No asssessment' };
<?
 foreach($assessments as $key => $value) {
  print "assessments['$key'] = '$value';\n";
}
?>
// -->
]]>
</script>

<? if ($write_access) {
	$statistics = $doc_assessments->get_statistics();
?>

<script language="javascript">
  view_xid = <?=$doc_assessments->data->xid?>;
  id_pool = <?=$id_pool?>;
  todo = new Array(<? for($i=0; $i < sizeof($statistics["TODO"]); $i++) {
		print ($i > 0 ? "," : "") . "'a_" . $doc_assessments->get_relative_path($statistics["TODO"][$i]) . "'";
   }
   ?>);

</script>
      
      <form id="form_save" method="post" action="assess.php" target="xrai-assessing">
      <input type="hidden" name="id_pool" value="<?=$id_pool?>"/>
      <input type="hidden" name="file" value="<?=htmlspecialchars($file)?>"/>
      <input id="form_assessments" type="hidden" name="assessments" value=""/>
</form>

<iframe id="assessing" name="xrai-assessing" align="middle" onclick="this.visibility='hide'"
  style="visibility: hidden; position: fixed; left: 10%; top: 10%; width: <?=($do_debug ? 90 : 60)?>%; height: <?=($do_debug ? 90 : 30)?>%; z-index: 1; background: white; opacity: 80%">
</iframe>



<div id="eval_div"  onclick="hideEval()" onmouseover="window.status='Click to assess the element(s)'" onmouseout="window.status=''">
<div id="eval_path"><div></div></div>

<table><tr>
<?

function judge($a) {
  global $assessments;
  print "<td><img "
    . " src='" . get_assessment_link($a,false) . "'"
    . " alt='$assessments[$a]'"
    . " id=\"asssess_$a\" onclick=\"assess(this,'$a',event); return false;\" "
    . " title=\"$assessments[$a]\" "
    . " name=\"assess\" "
    . " value=\"$a\" /></td>";
}
print '<td><table><tr>';
judge("U");
judge("00");
print '</tr></table></td>';


print '<td><table>';
  for($s=1; $s < 4; $s++) {
    print "<tr>";
    for($e = 1; $e < 4; $e++) judge("$e$s");
    print "</tr>\n";
  }

?></table></td></tr></table></div>

<? } // end of if (write_access)




// Functions called by the PHP (XML+XSL) file
// ==========================================



function color($txt) {
   global $keywords, $colours, $in_mathml;
  if ($in_mathml == 1) return $txt; 
  if (!$keywords) return $txt;
  else return preg_replace($keywords,$colours,$txt);
}

// Image URL callback
function show_art($file,$width,$height) {
  global $directory, $year,$media_url;
  $file = preg_replace('-\.gif$-',".png",$file);
 // Scale down figures 
  if ($width > 860) { $w = $width; $width = 860; $height *= 860/$w; } 
  print "<div><img src='$media_url/" . strtolower("$directory/$file") . "'"
  	. ($width > 0 ? " width='$width'" : "")
	. ($height > 0 ? " height='$height'" : "")
	. " /></div>\n";
}




$tags = array();
$xids = array();
// Callback function for a new XML element (div/span type)
// $mode is "span" or "div"
function print_tag($mode,$tag,$xpath,$count,$tcount) {
  global $doc_assessments, $xids;
  global $tags, $current_xid, $in_mathml;
  array_push($tags,$tag);
  if ($doc_assessments) $a = $doc_assessments->get_element_by_id($current_xid);

  // i:m represent the assessment values mask
  // i:cm represent the assessment values mask for descendants without a mask
  // i:a is the current assessed value
  // i:p post-id value
  print "<$mode s='' id='$current_xid' i:post='" . ($current_xid+$count) 
      . "' class='xmle' path='$xpath' nc='$tcount'" 
    . (sizeof($xids) > 0 ? " i:p='" . $xids[sizeof($xids)-1] . "'" : "")     
    . ($a ? " name='" . $a->get_assessment_wta() . "'"
          . ($a->is_inferred() ? " ii='yes'" : "")
          . ($a->is_inconsistant() ? " ic='yes'" : "")
          . ($a->is_in_pool() ? " ip='yes'" : "")
          : " name='U'")
    . ">";
  $xids[] = $current_xid;
  $current_xid++;
  if ($mode == "tr") print "<td class='xmlp'><span class='xml'>$tag</span></td>";
  elseif ($mode == "tbody") print "<tr class='xmlp'><td class='xmlp'><span class='xml'>$tag</span></td></tr>";
  else print "<span class='xml'>$tag</span>\n";
 
}

function end_tag_up() {
  global $tags, $xids;
  array_pop($tags);
  array_pop($xids);  
}

function end_tag($mode,$tag,$xpath) {
  global $doc_assessments, $in_mathml;
  switch($tag) {
    case "bdy": case "sec": case "ss1": case "ss2": case "bm": case "fm": case "bibl":
      if ($doc_assessments) $a = $doc_assessments->get_element("$xpath");
    print "<span class='xml' title='$xpath'>";
      print "/$tag</span>";
  }
}




// Process XML file with stylesheet (if not in cache)
if (!$xslt) print "<div class='error'>No XSLT processor defined !</div>";

print "<div id='inex' oncontextmenu=\"return show_nav(event);\" ondblclick='do_dblclick(event)' onclick='do_click(event)' onmouseover='inex_mouseover(event)'>\n";
// print "<h1>$title</h1>\n";

$precompute = false;
include("include/process_article.inc");
if ($current_xid != $postid+1) {
  ?>
    <script language="javascript">
      alert("Major error: database ids (<?=$current_xid?>) and document ids (<?=$postid+1?>) do not match. Don't assess this document and fill a bug report!");
    </script>
  <?
  exit;
}

if ($write_access) {
?>
          
<div id="s_nav" onclick="this.style.visibility='hidden'">
          <img src="img/left.png" alt="&lt;-" onclick="goto_previous_assessment()"/>
          <img src="img/right.png" alt="-&gt;" onclick="goto_next_assessment()"/>
</div>
          
<div id="alert_no_selected" class="warn_panel">
  Can't assess the selection because there is no selected elements.
</div>

<div id="s_div" class="status" >
  <div style="padding: 3pt">
  <span>
          <span><img onclick="save_assessments();" id="save" src="<?=$base_url?>/img/filenosave.png" alt="Save" title="Save assessments (shift+s)"/><div class="help_bottom">Save the assessements. <br/><b>Shortcut</b>: hold <code>shift</code> and press <code>s</code></div></span>
          <span><img src="<?=$base_url?>/img/greenled.png" alt="[S]"  title="Assess selected elements (control+g)" onclick="show_eval_selected(event.pageX,event.pageY)"/><div class="help_bottom">Assess the selected elements. Elements can be (de)selected by clicking on the <span class="xml">[tag</span> name while pressing the key <code>control</code>. It is also possible to select all the siblings (which are in the same state: assessed or not assessed) with a double-clic.<br/><b>Shortcut</b>: hold the key <code>shift</code> and press <code>g</code></div></span>
          <span><img src="<?=$base_url?>/img/redled.png" alt="[C]"  title="Clear selection (control+shift+g)" onclick="clear_selected()"/><div class="help_bottom">Clear the current element selection (put the mouse over the green disc for more help on selection).<br/><b>Shortcut</b>: hold the key <code>shift</code> and <code>control</code> and press <code>g</code></div></span>
  </span> 
   <span>
          <span><img src="img/fgauche.png" title="previous assessment (shift+left arrow)" alt="&lt;-" onclick="todo_previous()"/><div class="help_bottom">Go to the previous Assessment. <br/><b>Shortcut</b>: hold <code>shift</code> and press the left arrow key</div></span>
          
          <span><img src="img/fhaut.png" title="Go to the container (shift+up arrow)" alt="^" onclick="location='<?=$up_url?>'"/><div class="help_bottom">Go to the innermost containing collection. <br/><b>Shortcut</b>: hold <code>shift</code> and press the up arrow key</div></span>
          
          <span><img src="img/fdroit.png" title="Next assessment (shift+right arrow)" alt="-&gt;" onclick="todo_next()"/><div class="help_bottom">Go to the next Assessment. <br/><b>Shortcut</b>: hold <code>shift</code> and press the right arrow key</div></span>
   </span>
   <span id="stat_div" ><?=get_stats_string($statistics,true,true);?></span>
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
