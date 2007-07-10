<?
/**
    show_history.php
    Display user's history for a pool
    
    Copyright (C) 2006  Benjamin Piwowarski benjamin@bpiwowar.net

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
   $id_pool = $_REQUEST["id_pool"];
   print_xhtml_header();
?>
<html xmlns="http://www.w3.org/1999/xhtml"><head><title>History of pool <?=$id_pool?></title></head>
<style>
div.list span.pointer {
   cursor: pointer;
   font-weight: bold;
   color: #008;
}
</style>
<body>

<script language="javascript">
function showDocument(event) {
   var w = window.opener;
   if (w) {
      if (!event.target.id) return;
      w.location = "<?="$base_url/article?id_pool=$id_pool&amp;"?>" + event.target.id;
   } else alert("Cannot find the main window!");

}

</script>
<?

$res = $xrai_db->query("SELECT idfile, collection, filename, title, inpool, status, hasrelevant FROM history JOIN files ON idfile = id LEFT JOIN filestatus USING (idpool, idfile) WHERE idpool=? GROUP BY idfile, collection, filename, title, inpool, status, hasrelevant ORDER BY max(time) DESC LIMIT 500",array($id_pool));
if (DB::isError($res)) print "<div style='font-weight: bold'>Database error: " . $res->getUserInfo() . "</div>";
else if ($res->numRows() == 0) print "<div style='font-weight: bold'>No history</div>";
else {
   print "<div class='list' onclick='showDocument(event)'>";
   while ($row = $res->fetchRow()) {
      print "<div>";
     if ($row["inpool"] == $db_true) print "<span style=\"border: 1px dashed blue\" title=\"in pool\">";
     else print "<span>";
      $no_status  = false;
      switch($row["status"]) {
         case "0": print "<img style=\"vertical-align: center;\" src=\"$base_url/img/mode_highlight\" title=\"highlighting mode\" alt=\"[highlighting]\"/>"; break;
         case "1": print "<img style=\"vertical-align: center;\" src=\"$base_url/img/nok\" title=\"assessing mode (not validated)\" alt=\"[assessing]\"/>"; break;
         case "2": print "<img style=\"vertical-align: center;\"  src=\"$base_url/img/ok\" title=\"assessing mode (validated)\" alt=\"[validated]\"/>"; break;
         default: $no_status = 1;
      }
      if (!$no_status) print "<img src='$base_url/img/is_" .($row["hasrelevant"] == $db_true ? "relevant" : "irrelevant") . ".png' alt=\"" .($row["hasrelevant"] == $db_true ? "relevant" : "irrelevant") . "\"/>";
      print "</span> ";

      print "<span class='pointer' id='collection=$row[collection]&amp;file=$row[filename]'>";
      print htmlspecialchars($row["title"]) . "</span></div>";  
   }  
   print "</div>";
}
?>
</body>
</html>
