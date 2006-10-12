<?

header("Content-type: application/xml");

// Change the "state" attribute of a pool
// (C) 2005 B. Piwowarski

chdir("..");
@require_once("include/xrai.inc");
if (!$is_root) {
?><error>Access forbidden</error><?
   exit;
}

$idpool=$_REQUEST["idpool"];
$old = $xrai_db->getOne("SELECT enabled FROM $db_pools WHERE id=?",array($idpool));
if (DB::isError($old)) {
?><error pool="<?=$idpool?>">Database error while retrieving the pool information (<?=$old->getUserInfo();?>)</error><?
exit();
}

$old = $db_true == $old;
$newv = $old ? "false": "true";
$op = $xrai_db->query("UPDATE $db_pools SET enabled=$newv WHERE id=?",array($idpool));
if (DB::isError($op)) {
?><not-done pool="<?=$idpool?>" value="<?=$old?>">DB error while changing pool enabled flag</not-done><?
exit();
}?>
<done pool="<?=$idpool?>" value="<?=$newv?>"/>