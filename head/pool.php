<?

	/** Show a query pool (or everything within) 

		 (c) B. Piwowarski, 2003
	*/


include_once("include/xrai.inc");
include_once("include/assessments.inc");

if (!$id_pool) {
	header("Location: index.php");
	exit;
}



// Retrieve journals
// $jh = do_query("select directory,rank,title from journals");
// while ($r = mysql_fetch_row($jh)) {
//   $journals[$row[0]][$row[1]] = $row["assessment"];
// }


// Retrieve xpaths
if ($id_pool) $localisation[] = array("$pool[name]","$PHP_SELF", "Pool for topic $pool[id_pool]" );
make_header("Pool summary for topic $id_topic");

// Retrieva assessments


// $query = "SELECT name, title, inferred, assessment, inconsistant, count(*) n FROM $db_files f LEFT JOIN $db_assessments a ON a.id_pool =  $id_pool AND a.xid >= f.xid AND a.xid <= f.post WHERE f.parent = '' GROUP BY f.name, inferred, assessment, inconsistant ORDER BY f.name, inferred, assessment, inconsistant";

//  print "<div>$query</div>";

$todojs = "";

// $qh = sql_query($query);
$text = "";

while ($qh && $row = sql_fetch_array($qh)) {
	$a = $row["inconsistant"] == 'Y' ? "I" : $row["assessment"];
	if ($row["assessment"] == 'U' || $row["inconsistant"] == 'Y' && !$todo[$row["name"]]) {
		$todo[$row["name"]] = true;
		$todojs .= ($todojs ? "," : "todo = new Array(") . "'$row[name]'";
	}
   $assessments[$row["name"]][$a] += $row["n"];
   $stats["T"][$a] += $row["n"];
	$stats[$row["inferred"]][$a] += $row["n"];
	$total += $row["n"];
}





// Output totals
print "<h1>Statistics</h1>";
// $s = sql_get_row("SELECT sum(if(assessment='U',1,0)),count(*) FROM $db_assessments WHERE id_pool =  $id_pool AND in_pool='Y'");
?>

<div style="margin:0.2cm; border: 1px solid black; padding: 5px;">
<div><?=$total > 0 ? intval(10000*($stats["T"]["I"] + $stats["T"]["U"]) / $total)/100 : 0?> % of elements are to assess</div>
<div><?=$s[1]>0 ? intval($s[0]/$s[1] * 10000)/100 : 0 ?> % of original pool elements are to assess</div>
</div>

<?

print " <div style='text-align: center'><table class='stats'>";
print "<thead><tr>";
print '<td style="padding: 0.1cm">Assessment</td>';
foreach($sorted_assessments as $a) {
  if ($a == 'U') continue;
  print "<td>" . get_evaluation_img($a,false) . "</td>";
}
print "</tr></thead>";

foreach(array('Y' => 'Inferred','N' => 'Assessed','T' => '<b>Total</b>') as $i => $it) {
print '<tr style=""><td style="padding: 0.1cm">' . $it . '</td>';
foreach($sorted_assessments as $a) {
  if ($a == 'U') continue;
  print "<td style='padding: 0.1cm'>" . ($stats[$i][$a] ? $stats[$i][$a] : 0) . "</td>";
}
print "</tr>";
}
print "</table>";
print "</div>";
?>
<div style="margin-top: 0.1cm"><b>Number of elements to assess</b>: <?=$stats["T"]["I"] + $stats["T"]["U"]?> (<?=intval($stats["T"]["U"])?>  <img src='img/U.png' alt='?' title='to assess'/> + <?=intval($stats["T"]["I"])?> <img src='img/I.png' alt='B' title='inconsistant'/>)</div>

<?

// $global = sql_get_row("SELECT count(*),SUM(IF(a.xid is null,0,1)),SUM(IF(a2.xid is null,0,1)) n FROM $db_files f LEFT JOIN $db_assessments a ON a.id_pool =  $id_pool AND a.xid >= f.xid AND a.xid <= f.post AND a.xid = 'U' LEFT JOIN $db_assessments a2 ON a2.id_pool =  $id_pool AND a2.xid >= f.xid AND a2.xid <= f.post AND a2.xid <> 'U'  WHERE f.type = 'xml' ");

// print "$global[0], $global[1], $global[2]<br/>";

if ($todojs) $todojs .= ");\n";


// Print the volumes

print "<h1>Collections</h1>";

$ch = sql_query("SELECT id, title from collections order by id");
while ($row = sql_fetch_array($ch)) {
   print "<div>";
   print " <a id='$row[id]' href=\"collections/$row[id]?id_pool=$id_pool\">" . htmlspecialchars($row["title"]) . "</a>";
   print "</div>";
}
sql_free($ch);


?>
<script language="javascript">
 view_xid = 0;
 id_pool=<?=$id_pool?>;
 <?=$todojs?>
</script>


<div id="s_div" class="status">
<div style="padding: 3pt">
 <span>
       <img id="previous_to_assess" src="img/fgauche.png" title="Previous assessment (shift + left arrow)" onclick="todo_previous()"/>
       <img id="next_to_assess" title="Next assessment (shift + right arrow)" src="img/fdroit.png" onclick="todo_next()"/>
 </span>
 <span>
  <?=get_stats_string($stats["T"])?>
  </span>
 </div>
</div>
<script language="javascript"  src="<?=$base_url?>/js/collection.js"/>
<script language="javascript">
  up_url = null;
  document.onkeypress = collection_keypress;
</script>

<?
 make_footer();
?>
