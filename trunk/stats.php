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

function changeMainAnswer() {
 // if xmlhttp shows "loaded"
   x.alt = "<?=$old ? "false":"true"?>";
}

function changeMain(id) {
  var xmlhttp=new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
     if (xmlhttp.readyState==4) {
      // if "OK"
      if (xmlhttp.status==200) {
         var root = xmlhttp.responseXML.firstChild;
         if (root.localName == "done") {
         var name = "m-" + root.getAttribute("pool");
         var l = document.getElementsByName("m-" + id);
//          alert("'" + name + "' -- " + l + " " + l.length + " - " + root);
         for(i = 0; i &lt; l.length; i++) {
               l[i].src = "<?=$base_url?>/img/" + (root.getAttribute("value") == "false" ? "redled":"greenled");
             }
         } else alert("Error: " + root.firstChild.nodeValue);
      } else alert("Problem retrieving XML data");
   }
  };
  xmlhttp.open("GET","<?=$base_url?>/iframe/change_main.php?idpool=" + id,true);
  xmlhttp.send(null);

}

</script>
<?
$res = $xrai_db->query("SELECT pools.id AS pool, pools.login as login, pools.idtopic as topic, ( SELECT count(*) AS count
           FROM filestatus
          WHERE filestatus.idpool = pools.id AND filestatus.status = 2) AS done, ( SELECT count(*) AS count
           FROM filestatus
          WHERE filestatus.idpool = pools.id AND filestatus.status < 2) AS todo, enabled, main
   FROM pools
  WHERE pools.state::text = 'official' ORDER BY todo desc, idtopic, done");

$topics = array();
if (DB::isError($res)) fatal_error("DB error",$res);
?> 
<div>
View:
<ul>
<li><a href="#bypool">By pool</a></li>
<li><a href="#bytopic">By topic</a></li>
<li><a href="#logins">Logins that have completed their assessments</a></li>
</ul>
</div>
<a name="bypool"/><h1>Pools</h1><table class="stats"><thead><tr><th>Official</th><th>Editable</th><th>Pool ID</th><th>Topic ID</th><th>login</th><th># assessed docs</th><th># unassessed docs</th></tr></thead><tbody><?
while ($row=$res->fetchRow()) {
   $row["enabled"] = $db_true == $row["enabled"];
   if (!is_array($topics[$row["topic"]])) 
      $topics[$row["topic"]] = array(array(),array(), array());
   $topicid = $row["topic"];
   $login = $row["login"];
   
   $is_done = ($row["todo"] == 0 && $row["done"] > 0) ? 1 : 0;
   if (!is_array($logins[$login])) $logins[]  = array(0,0);
   $logins[$login][$is_done]++;
   $topics[$row["topic"]]["main"] = $topics[$row["topic"]]["main"] || $row["main"];
   $topics[$row["topic"]][$is_done][$login] = array(
   " <img style=\"vertical-align: middle;\" onclick=\"changeMain($row[pool])\" name=\"m-$row[pool]\" src=\"$base_url/img/" . ($row["main"] ? "greenled" : "redled") 
   . "\" alt=\"" . ($row[enabled]?"true" : "false") . "\"/>"
   . " <a href=\"$base_url/pool?id_pool=$row[pool]\">$row[login]</a>"
   ,$row["todo"], $row["done"]);
   ?><tr>
<td><img onclick="changeMain(<?=$row["pool"]?>)" name="m-<?=$row["pool"]?>" src="<?="$base_url/img/" . ($row["main"] ? "greenled" : "redled")?>" alt="<?=$row["main"]?"true" : "false"?>"/></td>
<td><img onclick="changeState(<?=$row["pool"]?>)" id="e-<?=$row["pool"]?>" src="<?="$base_url/img/" . ($row["enabled"] ? "greenled" : "redled")?>" alt="<?=$row["enabled"]?"true" : "false"?>"/></td><td><a href="<?="$base_url/pool?id_pool=$row[pool]"?>"><?=$row["pool"]?></a></td><td><?=$row["topic"]?></td><td><?=$row["login"]?></td><td><?=$row["done"]?></td><td><?=$row[todo]?></td></tr><?
}
?></tbody></table><?

      // Summary for topics
function getBar($title, $n, $d) {
   $s = floor(150*($d/($n+$d)));
      
   return "<span title=\"$title\"><span style='display: inline; padding-left: $s"  
   . "px; background: #aaf; border: 1px solid #000; border-right:0;'></span>"
   . "<span style='display: inline; padding-left: "
   . (150-$s)
   . "px; background: transparent; border: 1px solid #000; border-left: 0;'></span></span>";
}

?> <a name="bytopic"/><h1>Topics</h1><?

$main_warn = false;
foreach($topics as $id => $status) {
   if ((!$status["main"]) && (sizeof($status[1]) > 0)) {
      if (!$main_warn) {
         $main_warn = true;
         print "<h2>Warning: finished pool without main</h2><ul>";
      }
      print "<li><a href='#topic_$id'>Topic $id (" . sizeof($status[1]) . " finished)</a></li>";
   }
}

if ($main_warn) print "</ul>";
?>


<table class="stats"><thead><tr><th>Topic ID</th><th># finished pools</th><th># not finished</th></tr></thead><tbody><?

function myorder($x,$y) {
   $z = sizeof($y[0]) - sizeof($x[0]);
   if ($z != 0) return $z;
   return  sizeof($x[1]) - sizeof($y[1]);
}
uasort($topics,"myorder");
foreach($topics as $id => $status) {
 ?><tr><td><a name="topic_<?=$id?>"/><?=$id?></td>
 <td><?=sizeof($status[1])?>
<div style="font-size:small;">
<? 
   foreach($status[1] as $key => $value) 
      print "<div>$value[0]</div>";
?></div>
</td>
<td><?=sizeof($status[0])?><div style="font-size:small; text-align: left;"><?
   foreach($status[0] as $key => $value) {
      print "<div style='padding: 2px;'>";
      print getBar(getPercentage($value[2], $value[1]+$value[2]) . " %  done", $value[1], $value[2]);
      print " $value[0]</div>";
   }
?></div></td></tr><?
}
?></tbody></table>


<a name="logins"/>
<h2>Logins of finished pools</h2>
<? foreach($logins as $login => $v) if ($v[0] == 0 && $v[1] > 0) print "<div>$login</div>"; ?>
<?


make_footer();

?>