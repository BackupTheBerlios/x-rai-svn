<?


chdir("..");
include_once("include/xrai.inc");
include_once("include/assessments.inc");
include_once("admin/common.php");

$view_state = $_REQUEST["view_state"];
$action  = $_REQUEST["action"];

// Abort with user
ignore_user_abort(true);


if (!$pool_file) {
make_header("View pools" . ($view_state ? " - state \"$view_state\"" : "" ) );

// ---- Delete a pool

if ($action == "delete") {
  $expect = "I want to remove pool $_REQUEST[pool].";
  if ($_REQUEST["confirm"] == $expect) {
    sql_query("delete from $db_assessments where id_pool=$_REQUEST[pool]");
    sql_query("delete from $db_keywords where id_pool=$_REQUEST[pool]");
    sql_query("delete from $db_pools where id_pool=$_REQUEST[pool]");
    print "<div class='message'>Pool $_REQUEST[pool] was <em>deleted</em></div>";
  } else print "<div class='error'>Deletion was not confirmed (expected '$expect' and had '$confirm') </div>";
}

// ---- Clear a pool
if ($action == "clear") {
  $expect = "I want to clear pool $_REQUEST[pool].";
  if ($_REQUEST["confirm"] == $expect) {
    sql_query("delete from $db_assessments where id_pool=$_REQUEST[pool] and in_pool='N'");
    sql_query("update $db_assessments set assessment='U', inferred='N' where id_pool=$_REQUEST[pool]");
    print "<div class='message'>Pool $_REQUEST[pool] was <em>cleared</em></div>";
  } else print "<div class='error'>Clearing was not confirmed (expected '$expect' and had '$_REQUEST[confirm]') </div>";
}

// ---- Update a pool
if ($action == "update") {
	$pool = $_REQUEST["pool"];
	$edit_state = $_REQUEST["edit_state"];
	$edit_login = $_REQUEST["edit_login"];
	$edit_name = $_REQUEST["edit_name"];
	$edit_id_topic = $_REQUEST["edit_id_topic"];
	$edit_id_pool = $_REQUEST["edit_id_pool"];
	$prefix = $_REQUEST["edit_file_prefix"];
   if (!empty($prefix) && !preg_match('#/$#',$prefix)) $prefix .= "/";
   
    sql_query("update $db_pools set id_topic=$edit_id_topic, enabled='$_REQUEST[enabled]', state='$edit_state', name='$edit_name', login='$edit_login' where id_pool=$edit_id_pool");
	$pool = $edit_id_pool;
	$filename = $_FILES['edit_file']['tmp_name'];

	if (is_file($filename)) {
 		function startElement($parser, $tagname, $attrs) {
		 	global $pool, $already_to_assess, $currentfile, $doit, $prefix, $number_updated, $number_inserted, $db_assessments;
			if ($tagname == "file") $currentfile = $attrs["file"];

			if ($tagname == "path") {
				if ($currentfile) {
				$xid = path2id($prefix.$currentfile,$attrs["path"]);
				if (!$xid) {
					print "<div style='color:red'>Can't convert $currentfile#$attrs[path]</div>";
				} else {
					$x = $already_to_assess[$xid];
					$query = false;
		 			if ($x == "N") {
						$query = "UPDATE $db_assessments SET in_pool='Y' where id_pool='$pool' AND xid=$xid";
						$number_updated++;
					} elseif (!$x) {
						$query = "INSERT INTO $db_assessments (id_pool,in_pool,inferred,xid) VALUES ($pool,'Y','N',$xid)";
						$number_inserted++;
					}
					if ($query) {
// 						print "<div>$query</div>";
						sql_query($query);
					}
				}
				} else {
					print "<div style='color:red'>No current file for path $attrs[path]</div>";
				}
			}
		}

		function endElement($parser, $tagname) {
			if ($tagname == "file") $currentfile = false;
		}

		print "<div class='message'>Updating elements to assess for pool $pool</div>";
		flush();
 	    sql_query("delete from $db_assessments where id_pool=$pool and in_pool='Y' and assessment='U'"); // don't delete assessments
	    $qh = sql_query("select xid, in_pool from $db_assessments where id_pool=$pool"); // don't delete assessments
		while ($row = sql_fetch_array($qh)) {
			$already_to_assess[$row["xid"]] = $row["in_pool"];
		}
		sql_free($qh);
		$number_inserted = $number_updated = 0;
		$bar = new ProgressBar(0,filesize($filename));
		$fh = fopen($filename,"r");
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, FALSE);
		$bytes_read = 0;
		while ($data = fread($fh, 4096)) {
			$bytes_read += strlen($data);
			$bar->update($bytes_read);
    		if (!xml_parse($xml_parser, $data, feof($fh))) {
		        make_footer(sprintf("XML error: %s at line %d",
            	        xml_error_string(xml_get_error_code($xml_parser)),
                    	xml_get_current_line_number($xml_parser)));
    		}
		}
		?><div class="message"><?=$number_inserted?> elements inserted and <?=$number_updated?> elements updated</div><?
	}
    print "<div class='message'>Pool $pool was <em>updated</em></div>";
}



// ---- DISPLAY POOL LIST ----
// ---------------------------

if (empty($view_state)) {
	$qh = sql_query("SELECT state from $db_pools GROUP by state order by state");
	?><h1>Choose a pool state</h1><ul><?
	while($row = sql_fetch_array($qh)) {
		print "<li><a href='?view_state=$row[state]'>$row[state]</a></li>";
	}
	?></ul><?
	make_footer("");
	exit;
}


      $afile = "$assessmentsdir/$view_state.tgz";
      $statefile = "$assessmentsdir/waiting/$view_state";


?>
<h1>Assessments</h1>
<?  if (@is_file($afile)) {
   print "<p>Download assessments (<a href=\"$assessments_url/$view_state.tgz\">" . date("d M Y - H:i",filemtime("$afile")) . ", " . intval(filesize("$assessmentsdir/$view_state.tgz")/1024) . " kbytes</a>)</p>\n";
} else {
    print "<p>There is no generated assessment file.</p>";
}

?>
<iframe id="regenerate" style="visibility: hidden; position: fixed">
</iframe>

<script language="javascript">
function regenerate_afile() {
  document.getElementById("regenerate").src = "<?="$base_url/admin/regenerate.php?state=$view_state"?>";
  var x = document.getElementById("regenerate_p");
  x.style.visibility = "hidden";
  x.style.position="absolute";
  //updateStats(); // Let's go !
}
</script>

      <div id='updating_div' style='visibility:hidden; position: absolute;'><p>Assessments file <b>is now being processed</b> (since <span id="proc_since"></span> - <span id='pratio_span'>?</span>&nbsp;% done).</p>
      <div style='background: red; width: 90%; padding: 0; margin: 5%;'>
      <div id='pratio_bar' style='width: 0%; background: blue'>&nbsp;</div>
      </div>
      </div>
      
      <div id='waiting_div' style='visibility: hidden; position: absolute'><p>Assessments file <b>will be generated</b> (waiting since <span id="waiting_since">?</span>, last check was <span id="last_check">?</span>).</p>
      </div>
      <iframe name="uframe" src="<?=$base_url?>/admin/update_dpool_stat.php?dir=<?=rawurlencode($assessmentsdir)?>&amp;state=<?=$view_state?>" style="visibility:hidden; position: absolute" id="pratio_frame"  />
      <script language="javascript">
      // some useful variables
      var waiting_div = document.getElementById("waiting_div");      
      var updating_div = document.getElementById("updating_div");
      var pratio_bar = document.getElementById("pratio_bar");
      
      function update_text(id,s) {
         var x = document.getElementById(id);
         if (!x) return;
//          alert("Putting '"+ s + "' in " + x + ", " + x.firstChild+", " + x.id);
         if (!x.firstChild) x.appendChild(document.createTextNode(s));
         else x.replaceChild(document.createTextNode(s),x.firstChild);
      }
      
      function updateStats() {
         document.getElementById("pratio_frame").contentDocument.location.reload();
//          alert(document.getElementById("pratio_frame").contentDocument.location);
//          setTimeout("updateStats()", 5000);
      }
      </script>
            


<?
  if (is_dir("$assessmentsdir/waiting")) {
      
  if (!is_file("$assessmentsdir/waiting/$view_state")) 
    print "<p id='regenerate_p'>Click <a href=\"javascript:regenerate_afile()\">here</a> to (re)generate the assessment file.</p>";
  else {
  }  } else {
  ?><p class="warning">The assessment directory does not exist.</p><?
  }

//       fatal_error("STOP");

?>
<h1>Statistics of pool (state: <?=$view_state?>)</h1>
<?
//       print htmlspecialchars("SELECT a.id_pool, a.assessment, a.inconsistant, count(*) n, sum(if(in_pool='Y' and assessment='U',1,0)) pt, sum(if(in_pool='Y' and assessment<>'U',1,0)) pd FROM $db_assessments a, $db_pools p  where p.state='$view_state' AND p.id_pool = a.id_pool GROUP BY id_pool, assessment, inconsistant");

      $qh_pools = sql_query("SELECT id_pool FROM $db_pools WHERE state='$view_state' ");
     while ($pool = sql_fetch_array($qh_pools)) { 
   $qh = sql_query("SELECT id_pool, assessment, inconsistant, count(*) n, sum(if(in_pool='Y' and assessment='U',1,0)) pt, sum(if(in_pool='Y' and assessment<>'U',1,0)) pd FROM $db_assessments   where id_pool = $pool[id_pool] GROUP BY id_pool, assessment, inconsistant");
while ($row = sql_fetch_array($qh)) {
	$a = ($row["inconsistant"] == 'Y' ? 'I' : $row["assessment"]);
	$pools[$pool["id_pool"]][$a] += $row["n"];
	$pools[$pool["id_pool"]]["total"] += $row["n"];
   $pools[$pool["id_pool"]]["pd"] += $row["pd"];
   $pools[$pool["id_pool"]]["pt"] += $row["pt"];
}
sql_free($qh);
}
sql_free($qh_pools);
$qh = sql_query("SELECT * FROM $db_pools where state='$view_state' order by id_pool");

?>
<script language="javascript">
function get_element(id) {
  var e = document.getElementById(id);
  if (!e) { alert("Element with id " + id + " can't be found"); return; }
  return e;
}

function hidepanel(id) {
  get_element(id).style.visibility = "hidden";
}

function show(id) {
  var e = get_element(id);
  e.style.top = (window.pageYOffset + 30) + "px";
  e.style.left = (window.pageXOffset + 30) + "px";
  e.style.visibility = "visible";
}

function showDelete(id) {
  get_element('delete_id_pool').value = id;
  show('delete');
}

function showEmpty(id) {
  get_element('clear_id_pool').value = id;
  show('clear');
}

function showEdit(id) {
  get_element('edit_id_pool').value = id;
  get_element('edit_id_pool_2').firstChild.nodeValue = id;
  var x = get_element('id_topic_' + id).firstChild;
  get_element('edit_id_topic').value = x ? x.nodeValue : "";
  x = get_element('name_' + id).firstChild;
//   alert(get_element('name_' + id) + " AND " + x);
  get_element('edit_name').value = x ? x.nodeValue : "";
  get_element('edit_state_<?=$view_state?>').selected = true;
  var y = get_element("enabled_" + get_element('enabled_' + id).getAttribute("alt"));
  if (y) y.checked=true;
  get_element('edit_login').value = get_element('login_' + id).innerHTML;
  show('edit');
}

</script>


<div id="edit" class="askpanel">
<img onclick="hidepanel('edit');" style="float: right;" src="<?=$base_url?>/img/close.png" alt="close" title="Close the panel"/>

<p>Editing pool number <span id='edit_id_pool_2'>XXXX</span></p>

<form method="post" enctype="multipart/form-data" action="manage_pools.php">
  <input type="hidden" id="edit_id_pool" name="edit_id_pool" value=""/>
  <input type="hidden" name="view_state" value="<?=$view_state?>"/>
  <div><b>Login</b> <input type="text" name="edit_login" id="edit_login" value=""/></div>
  <div><b>Name</b> <input type="text" name="edit_name" id="edit_name" value=""/></div>
<div><b>Topic id</b>     <select name="edit_id_topic" id="edit_id_topic">
<?
$topics = mysql_query("SELECT id from $db_topics order by id");
while ($t = mysql_fetch_array($topics)) print "<option value='$t[0]'>$t[0]</option>\n";
?>
</select></div>
  <div><b>Status</b> <?=get_select_status("edit_state");?></div>
  <div><b title="If needed">Pool file</b> <input type="file" name="edit_file" value=""/></div>
  <div><b title="If needed">Pool file collection prefix</b> <input type="text" name="edit_file_prefix" value=""/></div>
  <div><b>Pool enabled</b> <input id="enabled_E" type="radio" name="enabled" value="Y"/>Yes <input type="radio" id="enabled_D" name="enabled" value="N"/>No</div>
  <div><input type="submit" name="action" value="update"/></div>
</form>
</div>

<div id="delete" class="askpanel">
<img onclick="hidepanel('delete');" style="float: right;" src="<?=$base_url?>/img/close.png" alt="close" title="Close the panel"/>
<p style="color:red">Warning: this pool will be deleted</p>
<p>You have to confirm by typing exactly 'I want to remove pool <em>id</em>.'</p>
<form method="post">
<input type="hidden" id="delete_id_pool" name="pool" value=""/>
<input type="hidden" name="action" value="delete"/>
<input type="text" name="confirm" value="*"/>
</form>
</div>

<div id="clear" class="askpanel">
<img onclick="hidepanel('clear');" style="float: right;" src="<?=$base_url?>/img/close.png" alt="close" title="Close the panel"/>
<p style="color:red">Warning: this pool will be cleared (all asssessments are reset)</p>
<p>You have to confirm by typing exactly 'I want to clear pool <em>id</em>.'</p>
<form method="post">
<input type="hidden" id="clear_id_pool" name="pool" value=""/>
<input type="hidden" name="action" value="clear"/>
<input type="text" name="confirm" value="*"/>
</form>
</div>
      <iframe id="update_pool" style="visibility: hidden; position: fixed">
      </iframe>

<p>
<table class='list'>
<thead><tr><th  style='background: white; border: 0;'/><th>Id</th><th>Topic id</th><th></th><th>Authorized login</th><th>Pool name</th>
<?
foreach(array_merge(array('I'),$sorted_assessments) as $a) {
	print "<th>" . get_assessment_img($a,false,false,true,$a == 'I') . "</th>";
}
?>
<script language="javascript">
   function toggle_enabled(id) {
   var t = document.getElementById("update_pool");
   if (!t) { alert("Bug"); return; }
   var enabled = get_element('enabled_' + id).getAttribute("alt");
//    alert(enabled);
   if (enabled=='E') enabled='N'; else enabled='Y';
//    alert(enabled);
   t.src = "update_pool.php?enabled="+enabled+"&amp;id_pool="+id;
   }
</script>
<th>% done</th></tr></thead>
<tbody>
<?
while ($row = sql_fetch_array($qh)) {
   $s = &$pools[$row["id_pool"]];
   $finished =  $s["U"]+$s["I"] == 0;
?>
      <tr<?=$finished ? " style='background: #88ff88'" : ""?>>
    <td style='background: white; white-space: nowrap;'>
      <img src="<?=$base_url?>/img/delete.png" alt="delete" title="Delete this pool" onclick="showDelete(<?=$row["id_pool"]?>)"/>
      <img src="<?=$base_url?>/img/empty.png" alt="empty" title="Remove assessments" onclick="showEmpty(<?=$row["id_pool"]?>)"/>
      <img src="<?=$base_url?>/img/edit.png" alt="edit" title="Edit this pool" onclick="showEdit(<?=$row["id_pool"]?>)"/>
    </td>
    <td><?=$row["id_pool"]?></td>
    <td id="id_topic_<?=$row["id_pool"]?>"><?=$row["id_topic"]?></td>
      <td><img id="enabled_<?=$row["id_pool"]?>" state="<?=$row["enabled"]?>" onclick="toggle_enabled(<?=$row["id_pool"]?>)" src="<?="$base_url/img/" . ($row["enabled"] == 'Y' ? 'green' : 'red')?>led.png" title="<?=$row["enabled"] == 'Y' ? "Pool enabled" : "Pool disabled"?>" alt="<?=$row["enabled"] == 'Y' ? 'E' : 'D'?>"/></td>
	<td id="login_<?=$row["id_pool"]?>"><?=$row["login"]?></td>
    <td style='border-right: 1pt solid blue'><a id="name_<?=$row["id_pool"]?>" href="<?="$base_url/pool.php?id_pool=$row[id_pool]"?>"><?=$row["name"]?></a></td>
	<?
	foreach(array_merge(array('I'),$sorted_assessments) as $a) {
		print "<td style='width: 3em' title='" . ($s["total"] > 0 ? intval(1000 * ($s[$a]/$s["total"]))/10  : "?") . " %'>" . intval($s[$a])  . " </td>";
	}
   $allp = $s["total"] > 0 ? intval(100 * (1. - (($s["U"]+$s["I"])/$s["total"]))) : "?"; 
   $poolp = (($s["pd"]+$s["pt"]) > 0 ? intval(100 *  ($s["pd"]/($s["pd"] + $s["pt"])))  : "?") ;
   $color =  str_pad(dechex(intval(($poolp)*2.55)),2,"0",STR_PAD_LEFT); 
   $ncolor =  str_pad(dechex(intval((100.-$poolp)*2.55)),2,"0",STR_PAD_LEFT); 
	print "<td style=' border-left: 1pt solid blue;" . ($finished ? " color: white; font-weight: bold;" :   "color:black;") . " background: #{$ncolor}{$color}00" . "'><div title='original pool elements ($allp % over all elements)'>$poolp&nbsp;%</div></td>";
	?>
 </tr>
    <?
}

?>
</tbody>
</table>
</p>
<style type="text/css">
	form div { margin-top: 2pt; padding: 0.1cm; border-top: 1pt solid black; border-bottom: 1pt solid black; background: #eeeeff}
 	form div span { color: #000099; font-weight: bold; }
</style>


<?
make_footer();
exit;
}


?>
