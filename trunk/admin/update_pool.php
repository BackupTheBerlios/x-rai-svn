<?header("Cache:0");?>
<html><head><title>Update of pool</title></head><body><?

chdir("..");
require_once("include/xrai.inc");

// Update pool state
// For now, only copes with pool 'enabled' state

$pool = $_REQUEST["id_pool"];
$enabled = $_REQUEST["enabled"];
$result = sql_query("UPDATE pools SET enabled='$enabled' WHERE id_pool=$pool");
if ($result) {
?>
<script language="javascript">
var y = parent.document.getElementById("enabled_<?=$pool?>");
if (!y) alert("Can't update the web page to reflect current status!");
else {
   y.setAttribute("alt","<?=$enabled=="Y"?"E":"D"?>");
   y.setAttribute("src","<?="$base_url/img/" . ($enabled == 'Y' ? 'green' : 'red')?>led.png");
   y.setAttribute("title","Pool <?$enabled=="Y"? "enabled":"disabled"?>");
}
</script>
<?
      print "Pool $pool is now in state $enabled";
}
?>
</body>
</html>