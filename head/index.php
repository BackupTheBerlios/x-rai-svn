<?php
include_once("include/xrai.inc");


make_header("Home");
	
	print "<h1>Choose a pool</h1>";
	$qh = do_query("select * from $db_pools " . ($is_root ? "" : " where login='$inex_user' ") . " order by id_pool");
	print "<ul>";
	while ($row = mysql_fetch_array($qh)) {
		$name = "Pool for topic $row[id_topic]" . ($is_root ? " ($row[login])" : "");
		print "<li><a href='pool.php?id_pool=$row[id_pool]'>$name</a></li>";
	}
	print "</ul>";
	mysql_free_result($qh);


?>


<h1>Browse the collection</h1>


<?php


$query = "SELECT name, title FROM $db_files f WHERE f.parent = '' ORDER BY f.name";

$qh = sql_query($query);
while ($row = sql_fetch_array($qh)) {
  if ($old == $row["name"]) continue;
  $old = $row["name"];
   print "<div>";
   print " <a id='$row[name]' href=\"collections/$row[name]\">" . htmlspecialchars($row["title"]) . "</a>";
   print "</div>";
   
}

sql_free($qh);


?>

</body>
</html>
