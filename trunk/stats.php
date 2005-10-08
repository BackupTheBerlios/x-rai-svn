<?

require("include/xrai.inc");
if (!$is_root) {
   header("Location: index.php");
   exit;
}

make_header("Statistics");
?>
<iframe style="display: none" src="about:blank" id="changeStateFrame"></iframe>
<script language="javascript">
function changeState(id) {
   var x = document.getElementById("changeStateFrame");
   x.src="<?=$base_url?>/iframe/change_state.php?idpool=" + id;
}
</script>
<?
$res = $xrai_db->query("SELECT pools.id AS pool, pools.login as login, pools.idtopic as topic, ( SELECT count(*) AS count
           FROM filestatus
          WHERE filestatus.idpool = pools.id AND filestatus.status = 2) AS done, ( SELECT count(*) AS count
           FROM filestatus
          WHERE filestatus.idpool = pools.id AND filestatus.status < 2) AS todo, enabled
   FROM pools
  WHERE pools.state::text = 'official' ORDER BY todo desc, done");

$topics = array();
if (DB::isError($res)) fatal_error("DB error",$res);
?> <h1>Pools</h1><table class="stats"><thead><tr><th>Editable</th><th>Pool ID</th><th>Topic ID</th><th>login</th><th># assessed docs</th><th># unassessed docs</th></tr></thead><tbody><?
while ($row=$res->fetchRow()) {
   $row["enabled"] = $db_true == $row["enabled"];
   if (!is_array($topics[$row["topic"]])) $topics[$row["topic"]] = array(array(),array());
   array_push($topics[$row["topic"]][$row["todo"] > 0 ? 0 : 1], "<a href=\"$base_url/pool?id_pool=$row[pool]\">$row[login]</a>");
   ?><tr><td><img onclick="changeState(<?=$row["pool"]?>)" id="e-<?=$row["pool"]?>" src="<?="$base_url/img/" . ($row["enabled"] ? "greenled" : "redled")?>" alt="<?=$row["enabled"]?"true" : "false"?>"/></td><td><a href="<?="$base_url/pool?id_pool=$row[pool]"?>"><?=$row["pool"]?></a></td><td><?=$row["topic"]?></td><td><?=$row["login"]?></td><td><?=$row[done]?></td><td><?=$row[todo]?></td></tr><?
}
?></tbody></table><?

?> <h1>Topics</h1><table class="stats"><thead><tr><th>Topic ID</th><th># finished pools</th><th># not finished</th></tr></thead><tbody><?
function myorder($x,$y) {
   $z = sizeof($y[0]) - sizeof($x[0]);
   if ($z != 0) return $z;
   return  sizeof($x[1]) - sizeof($y[1]);
}
uasort($topics,"myorder");
foreach($topics as $id => $status) {
 ?><tr><td><?=$id?></td><td><?=sizeof($status[1])?>
<div style="font-size:small;"><?=implode(", ",$status[1])?></div></td><td><?=sizeof($status[0])?><div style="font-size:small;"><?=implode(", ",$status[0])?></div></td></tr><?
}
?></tbody></table><?

make_footer();

?>