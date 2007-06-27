<?php
/**
    index.php
    Main page
    
    Copyright (C) 2003-2007  Benjamin Piwowarski benjamin@bpiwowar.net

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Library General Public
    License as published by the Free Software Foundation; either
    version 2 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Library General Public License for more details.

    You should have received a copy of the GNU Library General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
*/

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

$ch = $xrai_db->query("SELECT collection, title FROM $db_files WHERE parent is null ORDER BY title");
if (DB::isError($ch)) non_fatal_error("Could not retrieve collections",$ch->getUserInfo());
else {
   while ($row = $ch->fetchRow(DB_FETCHMODE_ASSOC)) {
      print "<div>";
      print " <a id='$row[id]' href=\"collections/$row[collection]\">" . htmlspecialchars($row["title"]) . "</a>";
      print "</div>";
   }
}

?>

</body>
</html>
