<html><head></head><body><?

// Change the state of a pool
// (C) 2005 B. Piwowarski

chdir("..");
@require_once("include/xrai.inc");
if (!$is_root) {
   header("Location: index.php");
   exit;
}

$idpool=$_REQUEST["idpool"];
$old = $xrai_db->getOne("SELECT enabled FROM $db_pools WHERE id=?",array($idpool));
if (DB::isError($old) || !$old) {
?><script language="javascript">alert("DB error while changing pool writable flag");</script><?
exit();
}

$old = $db_true == $old;
$newv = $old ? "false": "true";
$op = $xrai_db->query("UPDATE $db_pools SET enabled=not(enabled) WHERE id=?",array($idpool));
if (DB::isError($op)) {
?><script language="javascript">alert("DB error while changing pool writable flag");</script><?
exit();
}

?><script language="javascript">
   var ref = window.parent;
   var x = ref.document.getElementById("e-<?=$idpool?>");
   x.src = "<?="$base_url/img/" . ($old?"redled":"greenled")?>";
   x.alt = "<?=$old ? "false":"true"?>";
</script>
done (<?="$idpool, $old, " . ($old?"redled":"greenled")?>).
</body></html>