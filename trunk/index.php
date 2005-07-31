<?php
include_once("include/xrai.inc");


make_header("Home");
	
	print "<h1>Choose a pool</h1>";
	$qh = $xrai_db->query("select * from $db_pools " . ($is_root ? "" : " where login='$inex_user' ") . " order by id");
   if (DB::isError($qh)) {
     print "<div class=\"warning\">Error while retrieving pools</div>";
     if ($do_debug) print "<div>" . $qh->getUserInfo() . "</div>";
   } else {
      print "<ul>";
      while ($row = $qh->fetchRow(DB_FETCHMODE_ASSOC)) {
         $name = "Pool for topic $row[idtopic]" . ($is_root ? " ($row[login])" : "");
         print "<li><a href='pool.php?id_pool=$row[id]'>$name</a></li>";
      }
      print "</ul>";
   }


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
