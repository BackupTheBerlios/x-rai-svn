<?
  include_once("include/xrai.inc");
   $id_pool = $_REQUEST["id_pool"];
   print_xhtml_header();
?>
<html xmlns="http://www.w3.org/1999/xhtml"><head><title>History of pool <?=$id_pool?></title></head>
<style>
div.list div {
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
      w.location = "<?="$base_url/article?id_pool=$id_pool&amp;"?>" + event.target.id;
   } else alert("Cannot find the main window!");
}
</script>
<?

$res = $xrai_db->query("SELECT idfile, collection, filename, title, inpool, status, hasrelevant FROM history JOIN files ON idfile = id LEFT JOIN filestatus USING (idpool, idfile) WHERE idpool=? GROUP BY idfile, collection, filename, title, inpool, status, hasrelevant ORDER BY max(time) DESC LIMIT 50",array($id_pool));
if (DB::isError($res)) print "<div style='font-weight: bold'>Database error: " . $res->getUserInfo() . "</div>";
else if ($res->numRows() == 0) print "<div style='font-weight: bold'>No history</div>";
else {
   print "<div class='list' onclick='showDocument(event)'>";
   while ($row = $res->fetchRow()) {
      print "<div id='collection=$row[collection]&amp;file=$row[filename]'>" . htmlspecialchars($row["title"]) . "</div>";  
   }  
   print "</div>";
}
?>
</body>
</html>