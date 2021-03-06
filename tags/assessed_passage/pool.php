<?

   /** Show a query pool (or everything within)

       (c) B. Piwowarski, 2003
   */


require_once("include/xrai.inc");
require_once("include/astatus.inc");
// require_once("include/assessments.inc");

if (!$id_pool) {
   header("Location: index.php");
   exit;
}

if ($id_pool) $localisation[] = array("$pool[name]","$PHP_SELF", "Pool for topic $pool[idtopic]" );
make_header("Pool summary for topic $id_topic");


// Retrieve assessments


$todojs = "";
$res = &$xrai_db->query("SELECT sv.*,f.collection FROM $db_files f, $db_statusview sv WHERE f.parent is null AND f.id=sv.rootid AND idpool=?",array($id_pool));
if (DB::isError($res)) non_fatal_error("Error while retrieving assessments",$res->getUserInfo());
else {
   while ($row = $res->fetchRow()) {
      $s = ($row["status"] == 2 ? 2 : 1) * ($row["inpool"] == $db_true ? 1 : -1);
//       print "$row[collection] / $s / $row[inpool] $db_true / $row[count]<br/>";
      $a[$row["collection"]][$s] = $row["count"];
      $t[$s] = $row["count"];
      $total[$s]++;
      $todojs .= ($todojs ? "," : "todo = new Array(") . "'$row[collection]'";
   }
   $res->free();
}


// Output totals
print "<h1>Statistics</h1>";

$p1=getPercentage($t["1"] + $t["-1"],$t["1"]+$t["2"]+$t["-1"]+$t["-2"]);
$p2=getPercentage($t[1],$t[1]+$t[2]);

?>

<div>
<div><?=$p1?> % of documents are to assess</div>
<div><?=$p2?> % of original pool documents are to assess</div>
</div>
<?

if ($todojs) $todojs .= ");\n";



// Print the volumes

print "<h1>Collections</h1>";
$ch = $xrai_db->query("SELECT collection, title FROM $db_files WHERE parent is null ORDER BY title");
if (DB::isError($ch)) non_fatal_error("Could not retrieve collections",$ch->getUserInfo());
else {
   while ($row = $ch->fetchRow(DB_FETCHMODE_ASSOC)) {
      print "<div>";
      printStatus($a[$row["collection"]], $total);
      print " <a id='$row[collection]' href=\"collections/$row[collection]?id_pool=$id_pool\">" . htmlspecialchars($row["title"]) . "</a>";
      print "</div>";
   }
}


?>
<script language="javascript">
 id_pool=<?=$id_pool?>;
 <?=$todojs?>
</script>


<script language="javascript"  src="<?=$base_url?>/js/collection.js"/>
<script language="javascript">
  up_url = null;
  document.onkeypress = collection_keypress;
</script>

<?
 make_footer();
?>
