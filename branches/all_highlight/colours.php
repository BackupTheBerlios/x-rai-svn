<?
header("Pragma: no-cache");
include_once("include/xrai.inc");
$modes = array(array("colour","Colour"), array("border","Border colour"), array("background","Background"));
?>
<html><head><title>INEX - <? print $pool["name"]; ?> keyword colours</title>
<style type="text/css">
a { colour: white; text-decoration: none; }
table { border-spacing: 1; }
td { margin: 2pt; width: 10pt; height: 10pt }
.tooltip { width: 50%; visibility: hidden; position: absolute; background: #eeeeee; colour: #000000; border: 1px solid red; }
textarea, input { padding: 1pt; border: 1pt solid black; background: #eeeeee; }
</style>


<?
$colour = $_REQUEST["colour"];

if ($_REQUEST["action"] == "update" && ($id_pool>0)) {
//   print_r($_REQUEST["keywords"]);
  foreach($_REQUEST["keywords"] as $mode => $kw) {
  if (preg_match('-^\s*$-',$kw)) {
  	$res = $xrai_db->query("delete from $db_keywords where idpool=$id_pool and colour='$colour' and mode='$mode'");
  } else {
   $res = $xrai_db->autoCommit(false);
   if (!DB::isError($res)) $res = $xrai_db->query("delete from $db_keywords where idpool=? and colour=? and mode=?",array($id_pool,$colour,$mode));
  	if (!DB::isError($res)) $res = $xrai_db->query("insert into $db_keywords (idpool,colour, keywords, mode) values (?,?,?,?)",array($id_pool, $colour,$kw,$mode));
   if (!DB::isError($res)) $res = $xrai_db->commit();
   $res = $xrai_db->autoCommit(true);
  }
      if (DB::isError($res)) die("Could update database"  . ($do_debug ? ": " . $res->getUserInfo() : ""));

  }

  print "<div style='text-align: center; colour: blue; background: #cccccc;'>Keywords saved</div>\n";
  unset($colour);
  unset($keywords);
//   flush();
//   print "<script>close();</script>";
//   exit;
}

// (1) Choose a colour
if ($id_pool > 0)
if (!$colour) {
	$qh = &$xrai_db->query("select colour,mode,keywords from $db_keywords where idpool=? order by colour",array($id_pool));
   if (DB::isError($qh)) die("Could not retrieve data"  . ($do_debug ? ": " . $qh->getUserInfo() : ""));
	$old = "";
	while ($row = $qh->fetchRow()) {
		if ($old != $row["colour"]) {
			if ($old) print "</div>";
			$old = $row["colour"];
			$keywords[$old] = true;
			print "<div id='$old' class='tooltip'";
		}
		print "<div><b>$row[mode]:</b> ". htmlspecialchars($row["keywords"]) . "</div>";
	}
	if ($old) print "</div>";
	?>
		<script language="javascript">
			function go(c) {
				window.location="colours.php?id_pool=<?=$id_pool?>&colour=" + c;
			}
			
	var m = 5;
	
	function show_tip(event,id) {
   e = document.getElementById(id);
   var x = event.pageX + m;
   var y = event.pageY + m;
	
   if ((y + e.scrollHeight ) > (window.innerHeight + window.scrollY)) {
		y = y - e.scrollHeight - 2 * m;
   	if (y < 0) y = 0;
	} 
   
	if ((x + e.scrollWidth) > (window.innerWidth + window.scrollX)) {
		x = x - e.scrollWidth - 2 * m;
   	if  (x<0) x = 0;
	}
	
   e.style.left = x + "px";
   e.style.top = y + "px";
   if (e.style.visibility != "visible") e.style.visibility = "visible";
}

function hide_tip(id) {
	var e =  document.getElementById(id);
	e.style.visibility = "hidden";
}
		</script>
	<?

	print "<h1>Choose a colour</h1>";
	print "<table>\n";
	for($r = 0; $r < 256; $r += 48) {
		print "<tr>\n";
		for($g = 0; $g < 256; $g += 48) {
			for($b = 0; $b < 256; $b += 48) {
				$c = str_pad(dechex(($r<<16)|($g<<8)|$b), 6, "0", STR_PAD_LEFT);
				$kwd = $keywords["$c"];
				print "<td  style='" . ($kwd ? "border: 2pt outset red;" : "") . " background: #$c' onClick='go(\"$c\")'";
				if ($kwd) print " onmouseover='show_tip(event,\"$c\")' onmousemove='show_tip(event,\"$c\")' onmouseout='hide_tip(\"$c\")'";
				print ">&nbsp;</td>\n";
			}
		}
		print "</tr>\n";
	}
	print "</table>";
}

// (2) Modify keywords
else {
	$keywords = $xrai_db->getAssoc("select mode,keywords from $db_keywords where colour=? and idpool=?",false,array($colour,$id_pool));
   if (DB::isError($keywords)) die("Could not retrieve data"  . ($do_debug ? ": " . $keywords->getUserInfo() : ""));
?>
            <div><form>From here you can go <input type="hidden" name="id_pool" value="<?=$id_pool?>"/><input type="submit" name="action" value="back"/> to the main panel or </form>
			
	<form name="main" onsubmit="save_mode_keywords(current_mode)">
         put your keywords below (one per line) and 
         <input type="submit" name="action" value="update"/> your current keywords.
            <div style="colour: #444444; font-size: smaller; margin: 2px 0 2px 0">Your keywords <em>may</em> be perl regular expressions (but be careful with them). <br/>Ex. <code>optimi[sz](?:ation|ed)</code> will match optimisation, optimised, optimization and optimized. <b>Warning:</b> use <code>(?:AB|C)</code> and <em>not</em> <code>(AB|C)</code> to group subexpressions.
	</div>
      <div style="margin-top: 0.2cm">
      <span style="colour:#444444">Keyword highlighting mode</span>
      <select name="mode" id="mode" onchange="change_mode(this)"><? foreach($modes as $mode) { ?><option value="<?=$mode[0]?>"><?=$mode[1]?></option><? }?></select>
      <?
        foreach($modes as $mode) 
          print "<input type=\"hidden\" name=\"keywords[$mode[0]]\" value=\"" . htmlspecialchars($keywords[$mode[0]]) . "\"/>\n";
      ?>
      
        <span style="colour: <?=$colour?>" id="example">
          example
        </span>
      </div>
		<input type="hidden" name="id_pool" value="<?=$id_pool?>"/>
		<input type="hidden" name="colour" value="<?=$colour?>"/>
		</p>
		<textarea title="Leave empty to remove the highlight with that colour" cols="80" rows="10" name="scratch"></textarea>
      
		<script language="javascript">
        var current_mode = "<?=$modes[0][0]?>";
        function save_mode_keywords(mode) {
          document.forms["main"]["keywords[" + mode + "]"].value = document.forms["main"]["scratch"].value;
        }
        function load_mode_keywords(mode) {
           document.forms["main"]["scratch"].value = document.forms["main"]["keywords[" + mode + "]"].value;
        }
        document.forms["main"]["mode"].value = current_mode;
        load_mode_keywords(current_mode);
        
        function change_mode(x) {
          var y = document.getElementById("example");
          y.style.background = "#ffffff";
          y.style.colour = "#000000";
          y.style.border = "0";
          switch(x.value) {
            case "border": 
              y.style.border ="1px solid #<?=$colour?>";
              break;
            case "colour":
              y.style.colour = "#<?=$colour?>";
              break;
            case "background":
              y.style.background = "#<?=$colour?>";
              break;
            break;
            default: 
            alert("Bug: mode " + x.value + " is not defined");
          }
          save_mode_keywords(current_mode);
          current_mode = x.value;
          load_mode_keywords(current_mode);
        }
      </script>
      </form>
            </div>
            <?
}

?>
<p><a href='javascript:window.close()'>Close</a></p>
</body></html>

