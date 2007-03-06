<?

require("include/xrai.inc");
if (!$is_root) {
   header("Location: index.php");
   exit;
}

make_header("Statistics");

$state = $_GET["state"];
$states = $xrai_db->getAssoc("SELECT state, count(*) FROM pools GROUP BY state");
if (DB::isError($res)) fatal_error("DB error",$res);

print "<h1>Pool states</h1><ul>";
foreach($states as $s => $n) {
      print "<li><a href=\"?state=$s\">$s</a> ($n pools)</li>";
   }
   print "</ul>";
   
if (!($states[$state] > 0)) {
   make_footer();
   exit();
}

?>
<script language="javascript">

function change(images, src, title, alt) {
   for(i = 0; i &lt; images.length; i++) {
      images[i].src = src;
      images[i].setAttribute("title",title);
      images[i].setAttribute("alt",alt);
   }
}

changeHandler = function(xmlhttp, prefix, info) {
     if (xmlhttp.readyState==4) {
      // if "OK"
      if (xmlhttp.status==200) {
         var root = xmlhttp.responseXML.firstChild;
         var name = prefix + root.getAttribute("pool");
         var l = document.getElementsByName(name);

         if (root.localName == "done" || root.localName == "not-done") {
            var x = root.getAttribute("value") == "true" ? 1 : 0;
            change(l, "<?=$base_url?>/img/" + info[x][0], info[x][1], info[x][2]);
// , x ?  : "The pool is not the main one", x ? "main" : "not main");
            if (root.localName == "not-done") alert("Change could not be done: " + root.firstChild.nodeValue);  
         } else {
            alert("Error: " + root.firstChild.nodeValue);
         }
      } else alert("Problem retrieving XML data");
   }
}


function changeMain(id) {
  var xmlhttp=new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
   changeHandler(xmlhttp,"m-", new Array(new Array("redled","Not selected as main","not main"), new Array("greenled","Selected as main","main")));
  };
  change(document.getElementsByName("m-" + id), "na", "Processing...", "?"); 
  xmlhttp.open("GET","<?=$base_url?>/iframe/change_main.php?idpool=" + id,true);
  xmlhttp.send(null);

}


function changeState(id) {
  var xmlhttp=new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
   changeHandler(xmlhttp,"e-", new Array(new Array("locked","Locked pool","locked"), new Array("unlocked","Unlocked pool", "unlocked")));
  };
   change(document.getElementsByName("e-" + id), "na", "Processing...", "?"); 
  xmlhttp.open("GET","<?=$base_url?>/iframe/change_state.php?idpool=" + id,true);
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
  WHERE pools.state::text = '$state' ORDER BY todo desc, idtopic, done");

$topics = array();
if (DB::isError($res)) fatal_error("DB error",$res);
?> 
<div>
View:
<ul>
<li><a href="#bypool">By pool</a></li>
<li><a href="#bytopic">By topic</a></li>
<li><a href="#logins">Logins that have completed their assessments</a></li>
<li><a href="#unassessed">Unassessed topics</a></li>
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
   // array: 0-link, 1-todo, 2-done
   $image_main = " <img style=\"vertical-align: middle;\" onclick=\"changeMain($row[pool])\" name=\"m-$row[pool]\" src=\"$base_url/img/" . ($row["main"] ? "greenled" : "redled") 
   . "\"" . ($row["main"]? " alt='true' title='Selected as main'" : " alt='false' title='Not selected as main'") . "/>";
   $image_state = "<img style=\"vertical-align: middle;\"  onclick=\"changeState($row[pool])\" name=\"e-$row[pool]\" src=\"$base_url/img/" . ($row["enabled"] ? "unlocked" : "locked") . "\"" .  ($row["enabled"]? " alt='true' title='Pool is not locked'" : " alt='false' title='Pool is locked'")  . "/>";
   $topics[$row["topic"]][$is_done][$login] = array("$image_main $image_state" 
   . " <a href=\"$base_url/pool?id_pool=$row[pool]\">$row[login]</a>"
   ,$row["todo"], $row["done"]);
   ?><tr>
   <td><?=$image_main?></td>
   <td><?=$image_state?></td>
   <td><a href="<?="$base_url/pool?id_pool=$row[pool]"?>"><?=$row["pool"]?></a></td><td><?=$row["topic"]?></td><td><?=$row["login"]?></td><td><?=$row["done"]?></td><td><?=$row[todo]?></td></tr><?
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

function getMinDocToAssess(&$x) {
$m = -1;
   foreach($x as $login => $stats) {
      if ($m >= 0) $m = min($m, $stats[1]);
      else $m = $stats[1];
   }
  return $m >= 0 ? $m : 0;
}

function myorder($x,$y) {
   $z = sizeof($y[1]) - sizeof($x[1]);
   if ($z != 0) return $z;
   $z = getMinDocToAssess($x[0]) - getMinDocToAssess($y[0]);
   if ($z != 0) return $z;
   return sizeof($x[0]) - sizeof($y[0]);
}
uasort($topics,"myorder");
foreach($topics as $id => $status) {
 ?><tr><td><a name="topic_<?=$id?>"/><?=$id?></td>
 <td><?=sizeof($status[1])?>
<div style="font-size:small;">
<? 
   if (sizeof($status[1]) == 0) $unassessed[] = $id;
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

<a name="unassessed"/>
<h2>Unassessed topics</h2>
<? 
sort($unassessed);
foreach($unassessed as $topic) {
 print "<div>$topic</div>";
}?>

<a name="logins"/>
<h2>Logins of finished pools</h2>
<? foreach($logins as $login => $v) if ($v[0] == 0 && $v[1] > 0) print "<div>$login</div>"; ?>

<div style="border:1px solid red;">
<h2>Logins of <span style="color:red">not</span> finished pools</h2>
<? foreach($logins as $login => $v) if ($v[0] > 0) print "<div>$login $v[0] missing, $v[1] completed</div>"; ?>
</div>

<?


make_footer();

?>