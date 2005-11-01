<?

header("Content-type: application/xml");

// Change the "main" attribute of a pool
// (C) 2005 B. Piwowarski

chdir("..");
@require_once("include/xrai.inc");
if (!$is_root) {
?><error>Access forbidden</error><?
   exit;
}

$idpool=$_REQUEST["idpool"];
$old = $xrai_db->getOne("SELECT main FROM $db_pools WHERE id=?",array($idpool));
if (DB::isError($old)) {
?><error>Database error while retrieving the pool information (<?=$old->getUserInfo();?>)</error><?
exit();
}

$old = $db_true == $old;
$newv = $old ? "false": "true";
$op = $xrai_db->query("UPDATE $db_pools SET main=" . ($old ? "null" : "true") . " WHERE id=?",array($idpool));
if (DB::isError($op)) {
?><error>DB error while changing pool main flag</error><?
exit();
}?>
<done pool="<?=$idpool?>" value="<?=$newv?>"/>