<?php

require_once("include/xrai.inc");
require_once("include/assessments.inc");
ignore_user_abort(false);

$file=stripcslashes($_REQUEST["file"]);
$collection=$_REQUEST["collection"];
$aversion=&$_REQUEST["aversion"];
$toadd=&$_REQUEST["a"];
$toremove=&$_REQUEST["r"];
$id_pool=&$_REQUEST["id_pool"];
$docstatus=&$_REQUEST["docstatus"];
$hist=&$_REQUEST["hist"];

?>
<html>
<head><title>Assessment</title></head>
<body>
<script type="text/javascript">
   var ref = window.parent;
   ref.setSavingMessage("Connected");
</script>

<div style="background: #eeeeef; padding: 3pt">
<div><b>Assessing</b></div>
<div><b>Collection:</b> <?="$collection"?></div>
<div><b>File:</b> <?="$file"?></div>
<div><b>Base version:</b> <?="$aversion"?></div>
<div><b>Doc status:</b> <?="$docstatus"?></div>
<div><b>Stats:</b> <?=sizeof($toadd)?> element(s) to add/modify, <?=sizeof($toremove)?> to remove.</div>
</div>
<?

$assessments = Assessments::create($id_pool, $collection, $file);
if (DB::isError($assessments)) {
?><script type="text/javascript">alert("Assessments could not be saved (database error).");</script><?
} else if ($assessments->getVersion() != $aversion) {
?><script type="text/javascript">alert("Assessments could not be saved: the version you assessed has been modified by someone else.");</script><?
   if ($do_debug) print "<div>Current version is " . $assessments->getVersion() . " but base version is $aversion</div>";
} else {
   // Start the transaction
   $xrai_db->autoCommit(false);
   for($aaaa = true; $aaaa; $aaaa = false) { // useful for the breaking the process without exceptions
      $res = $assessments->setNextVersion();
      if (DB::isError($res)) break;
      $res = $assessments->setStatus($docstatus);
      if (DB::isError($res)) break;

      if (is_array($toremove)) foreach($toremove as $a) {
         $x = split(',',$a);
         if ($do_debug) print "<div>Removing $x[0] - $x[1] ($a)</div>";
         $res = $assessments->remove($x[0], $x[1]);
         if (DB::isError($res)) break 2;
      }

      if (is_array($toadd)) foreach($toadd as $a) {
         $x = split(',',$a);
         if ($do_debug) print "<div>" . ($x[0] == 1 ? "Modify " : "Insert ") . "$x[2] - $x[3] with $x[1] ($a)</div>";
         if ($x[0] == 1) $res = $assessments->modify($x[1], $x[2],$x[3]);
         else $res = $assessments->add($x[1], $x[2],$x[3]);
         if (DB::isError($res)) break 2;
      }
      $res = $xrai_db->commit();
   }


   if (DB::isError($res)) {
      ?><script type="text/javascript">saveOK = false; alert("Assessments could not be saved (database error).");</script><?
      if ($do_debug) print "<div>" . $res->getUserInfo() ."</div>";
   } else {
      ?><script type="text/javascript">saveOK = true; ref.aversion = <?=$assessments->getVersion()?></script><?
   }
}

?>
<script type="text/javascript">
   ref.setSavingMessage("Done");
   ref.XRai.saved(saveOK);
   if (saveOK) ref.Message.show("notice","<?=sizeof($toadd)?> assessent(s) added/modified, <?=sizeof($toremove)?> assessment(s) removed");
</script>

<?
   if (!DB::isError($res)) {
      // Save history (non blocking process)
      $xrai_db->autoCommit(true);
      $error=false;
      foreach($hist as $h) {
         if ($do_debug) print "<div>Saving hist $h</div>\n";
         $h = split(',',$h);
         while (true) {
            $res = $startid = Paths::getPathId($h[2]); if (DB::isError($startid)) break;
            $res = $endid = Paths::getPathId($h[3]); if (DB::isError($endid)) break;
            $res = $xrai_db->autoExecute($db_history, array("idpool" => $assessments->idPool, "idfile" => $assessments->idFile,
                     "startpath" => $startid, "endpath" => $endid, "action" => $h[0], "time" => $h[1]));
            break;
         }
         if (DB::isError($res)) {
            if ($do_debug) print "<div>" . $res->getUserInfo() ."</div>";
            $error = true;
         }
      }
      if ($error) {
         ?><script type="text/javascript">
            ref.Message.show("warning","Error while saving history.");
         </script><?
      }
   }
?>

</body>
</html>
