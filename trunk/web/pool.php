<?
/**
    pool.php
    Show the pool status and links to the documents to assess
    
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
$nbRelevant = $nbNotRelevant = $nbToAssess = 0;

$res = &$xrai_db->query("SELECT ta.idpool, root.id AS rootid, root.collection, ta.status, ta.inpool, sum(CASE WHEN ta.hasRelevant AND ta.status = 2 THEN 1 ELSE 0 END) as nbRelevant, SUM(CASE WHEN not(ta.hasRelevant) AND ta.status = 2 THEN 1 ELSE 0 END) as nbNotRelevant, count(*) AS count
  FROM $db_files root, $db_files f, $db_filestatus ta
  WHERE idpool=? AND root.parent is null AND root.pre <= f.pre AND root.post >= f.pre AND ta.idfile = f.id
  GROUP BY ta.idpool, root.id, root.collection, ta.status, ta.inpool",array($id_pool));
// print_r($res);
if (DB::isError($res)) non_fatal_error("Error while retrieving assessments",$res->getUserInfo());
else {
   while ($row = $res->fetchRow()) {
      $s = ($row["status"] == 2 ? 2 : 1) * ($row["inpool"] == $db_true ? 1 : -1);
//       print "$row[collection] / $s / $row[inpool] $db_true / $row[count]<br/>";
      $a[$row["collection"]][$s] = $row["count"];
      $t[$s] = $row["count"];
      $total[$s] += $row["count"];
      $todojs .= ($todojs ? "," : "todo = new Array(") . "'$row[collection]'";
      $nbToAssess += $row["count"] - $row["nbrelevant"] - $row["nbnotrelevant"];
      $nbRelevant += $row["nbrelevant"];
      $nbNotRelevant += $row["nbnotrelevant"];

   }
   $res->free();
}
//       if ($is_root) print htmlentities($xrai_db->last_query);
      print "<div class='info'><b>Informations about this view</b>: $nbToAssess documents need to be assessed; among the assessed documents, $nbRelevant contain relevant passage(s) and $nbNotRelevant do not.</div>";


// Output totals
print "<h1>Statistics</h1>";

$p1=getPercentage($t["1"] + $t["-1"],$t["1"]+$t["2"]+$t["-1"]+$t["-2"]);
$p2=getPercentage($t[1],$t[1]+$t[2]);

?>

<div>
<div title="<?=$t["1"] + $t["-1"]?> out of <?=$t["1"]+$t["2"]+$t["-1"]+$t["-2"]?> "><?=$p1?> % of documents are to assess</div>
<div title="<?=$t["1"] ?> out of <?=$t["1"]+$t["2"]?> "><?=$p2?> % of original pool documents are to assess</div>
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
      print " <a id='$row[collection]' href=\"collections$phpext/$row[collection]?id_pool=$id_pool\">" . htmlspecialchars($row["title"]) . "</a>";
      print "</div>";
   }
}


?>
<script language="javascript">
 id_pool=<?=$id_pool?$id_pool : 0?>;
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
