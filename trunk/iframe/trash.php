<?

chdir("..");
require_once("include/xrai.inc");
require_once("include/assessments.inc");

// Remove all the assessments from an article view

$collection=$_REQUEST["collection"];
$file=$_REQUEST["file"];
$idpool = $_REQUEST["idpool"];
?>
<html>
<head><title>Assessment</title></head>
<body>
<script type="text/javascript">
   var ref = window.parent;
   ref.setSavingMessage("Connected");
</script>
<?

$xrai_db->autoCommit(false);

for($zzz = true; $zzz; $zzz = false) {
   $res = $idfile = Files::getFileID($collection,$file);
   if (DB::isError($idfile)) break;
   $res = $xrai_db->query("DELETE FROM $db_assessments WHERE idpool=? AND idfile=?",array($idpool, $idfile));
   if (DB::isError($res)) break;
   $res = $xrai_db->query("UPDATE $db_filestatus SET version=1, status=0 WHERE idpool=? AND idfile=? AND inpool",array($idfile, $idpool));
   if (DB::isError($res)) break;
   $res = $xrai_db->query("DELETE FROM $db_filestatus WHERE idpool=? AND not(inpool)",array($idpool));
   if (DB::isError($res)) break;
   $res = $xrai_db->autoExecute($db_history, array("idpool" => $idpool, "idfile" => $idfile, "action" => "E", "time" => $_REQUEST["time"]));

   if (DB::isError($res)) break;
   $res = $xrai_db->commit();
}

if (DB::isError($res)) {
   if ($do_debug) print "<div>" . $res->getUserInfo() ."</div>";
   $error = true;
}

?>
<script type="text/javascript">
<? if (DB::isError($res)) { ?>
      ref.Message.show("warning","Error while erasing.");
      ref.document.getElementById("erasing").style.display = "block";
<? } else { ?>
   ref.location.reload();
<? } ?>
</script>

</body>
</html>