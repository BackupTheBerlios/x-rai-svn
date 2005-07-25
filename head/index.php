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


<h1>Browse the collections</h1>


<?php

$ch = sql_query("SELECT id, title from collections order by id");
while ($row = sql_fetch_array($ch)) {
   print "<div>";
   print " <a id='$row[id]' href=\"collections/$row[id]\">" . htmlspecialchars($row["title"]) . "</a>";
   print "</div>";
}
sql_free($ch);

?>

</body>
</html>
