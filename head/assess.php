<?php

header("Pragma: no-cache");
require_once("include/xrai.inc");
require_once("include/assessments.inc");
ignore_user_abort(false);

// $paths =&$_REQUEST["paths"];
// $assess = &$_REQUEST["assess"];
parse_str($_REQUEST["assessments"],$assessments);
// print nl2br(print_r($to_assess,true));
// exit;
$to_assess = &$assessments["assess"];
$timestamps = &$assessments["ts"];
// print nl2br(print_r($to_assess,true));

// Variables: $id_pool, $directory, $file, $path
?>
<html>
<head><title>Assessment</title></head>
<body>
<div style="background: #eeeeef; padding: 3pt">
<div><b>Assessing</b></div>
<div><b>File:</b> <?="$file"?></div>
<div>
<b>Path(s):</b> 
<? foreach($to_assess as $path => $assess) print "<div style='margin-left:0.5cm;'>$path (" . get_evaluation_img($assess,false,false,true) . " " .  $doc_assessments[$assess]. ")</div>";
 if ($do_siblings == "yes") print " <i>and its siblings</i>"; 
 //print_r($to_assess);
 ?>
</div>
</div>

<div><a href="javascript:window.parent.document.getElementById('assessing').style.visibility = 'hidden'">Hide</a></div>

<?
$header_done = 1;

// (1) Retrieve & add assessment ---------------------------

$doc_assessments = new Assessments($id_pool, "$file", "","");
$f = false;

// if ($do_siblings == "yes") {
// 	if (sizeof($assess) > 1) fatal_error("Sibling mode AND more than one path !");
// 	$r = path2record("$directory/$file","$paths[0]");
// 	if (!$r) fatal_error("No record for $paths[0] in $directory/$file");
// 	if (!preg_match("#^(.*)/[^/]+#", $paths[0], $match)) fatal_error("Can't compute the parent path of $paths[0]");
// 	$ppath = $match[1];
// 	$qh = sql_query("SELECT tag, rank FROM map WHERE parent=$r[parent]");
// 	while ($row = sql_fetch_array($qh)) {
// 		$cpath = "$ppath/$row[0][$row[1]]";
// 		$e = &$doc_assessments->get_element("$ppath/$row[0][$row[1]]");
// 		if ((!$e || ($e->get_assessment() == 'U') || $assess == "U") && $paths[0] != $cpath) {
// 			$paths[] = $cpath;
// 		}
// 	}
// 	sql_free($qh);
// 
// }


// (2) Update database -------------------------------------

$doc_assessments->add_assessments($to_assess);
if ($do_debug) $doc_assessments->print_debug();
$doc_assessments->inference();
if ($do_debug) $doc_assessments->print_changes();
if ($do_debug) $doc_assessments->print_debug();

if (!$doc_assessments->update_database(true)) {
	?>
	<script type="text/javascript">
	alert("Can't assess <?=$path?>  as <?=$assessments[$assess]?>: constraints violation.");
	window.parent.document.getElementById("assessing").style.visibility = "hidden";
	</script>
	<?
	exit;
}



// (3) Update document view --------------------------------

$doc_assessments->update_masks();
$statistics = $doc_assessments->get_statistics();

?>
<script type='text/javascript'>
var p = window.parent;
<?
  print_js_stats_string("p",$statistics);
if (!$do_debug) { ?> setTimeout('window.parent.document.getElementById("assessing").style.visibility = "hidden"',500);<? }


?>

window.parent.saved();

window.parent.todo = new Array(<? for($i=0; $i < sizeof($statistics[TODO]); $i++) {
 	print ($i > 0 ? "," : "") . "'a_" . $doc_assessments->get_relative_path($statistics["TODO"][$i]) . "'";
    }
   
    ?>); 
</script>
<?
      $datestring = gmstrftime("%Y-%m-%d %H:%M:%S");
      // Save some statistics
   foreach($timestamps as $xid => $ts) {
   if ($do_debug) print "<div>$xid has ts = $ts</div>";
      sql_query("INSERT INTO statistics (id_pool,client_time,server_time,xid,assessment) values"
      . "($id_pool,\"$ts\",\"$datestring\",$xid,\"" . $to_assess[$xid] . "\")",false);
   }
?>

<div style='color: blue;'>Done</div>
<div><a href="javascript:window.parent.document.getElementById('assessing').style.visibility = 'hidden'">Hide</a></div>
</body>
</html>

