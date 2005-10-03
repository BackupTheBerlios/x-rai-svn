<?

require("include/xrai.inc");
if (!$is_root) {
   header("Location: index.php");
   exit;
}

make_header("Statistics");

$res = $xrai_db->query("SELECT pools.id AS pool, pools.login as login, pools.idtopic as topic, ( SELECT count(*) AS count
           FROM filestatus
          WHERE filestatus.idpool = pools.id AND filestatus.status = 2) AS done, ( SELECT count(*) AS count
           FROM filestatus
          WHERE filestatus.idpool = pools.id AND filestatus.status < 2) AS todo
   FROM pools
  WHERE pools.state::text = 'official' ORDER BY todo desc, done");

$topics = array();
if (DB::isError($res)) fatal_error("DB error",$res);
?> <h1>Pools</h1><table class="stats"><thead><tr><th>Pool ID</th><th>Topic ID</th><th>login</th><th># assessed docs</th><th># unassessed docs</th></tr></thead><tbody><?
while ($row=$res->fetchRow()) {
   $topics[$row["topic"]][$row["todo"] > 0 ? 0 : 1] ++;
   ?><tr><td><?=$row["pool"]?></td><td><?=$row["topic"]?></td><td><?=$row["login"]?></td><td><?=$row[done]?></td><td><?=$row[todo]?></td></tr><?
}
?></tbody></table><?

?> <h1>Topics</h1><table class="stats"><thead><tr><th>Topic ID</th><th># finished pools</th><th># not finished</th></tr></thead><tbody><?
function myorder($x,$y) {
   $z = $y[0] - $x[0];
   if ($z != 0) return $z;
   return  $x[1] - $y[1];
}
uasort($topics,"myorder");
foreach($topics as $id => $status) {
 ?><tr><td><?=$id?></td><td><?=$status[1] - 0?></td><td><?=$status[0] - 0?></td></tr><?
}
?></tbody></table><?

make_footer();

?>