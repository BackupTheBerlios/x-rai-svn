<?php

require_once("include/xrai.inc");
require_once("include/assessments.inc");
ignore_user_abort(false);

$file=$_REQUEST["file"];
$collection=$_REQUEST["collection"];
$aversion=&$_REQUEST["aversion"];
$toadd=&$_REQUEST["a"];
$toremove=&$_REQUEST["r"];
$id_pool=&$_REQUEST["id_pool"];

?>
<html>
<head><title>Assessment</title></head>
<body>
<script type="text/javascript">
   var ref = <?=$do_debug ? "window.opener" : "window.parent"?>;
   ref.setSavingMessage("Connected");
</script>

<div style="background: #eeeeef; padding: 3pt">
<div><b>Assessing</b></div>
<div><b>Collection:</b> <?="$collection"?></div>
<div><b>File:</b> <?="$file"?></div>
<div><b>Base version:</b> <?="$aversion"?></div>
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
      foreach($toremove as $a) {
         $x = split(',',$a);
         if ($do_debug) print "<div>Removing $x[1] - $x[2] ($a)</div>";
         $res = $assessments->remove($x[1], $x[2]);
         if (DB::isError($res)) break 2;
      }

      foreach($toadd as $a) {
         $x = split(',',$a);
         if ($do_debug) print "<div>" . ($x[1] == 1 ? "Modify " : "Insert ") . "$x[3] - $x[4] with $x[2] ($a)</div>";
         if ($x[1] == 1) $res = $assessments->modify($x[2], $x[3],$x[4]);
         else $res = $assessments->add($x[2], $x[3],$x[4]);
         if (DB::isError($res)) break 2;
      }
      $res = $assessments->setNextVersion();
      if (DB::isError($res)) break;
      $res = $xrai_db->commit();
   }
   
   if (DB::isError($res)) {
      ?><script type="text/javascript">alert("Assessments could not be saved (database error).");</script><?
      if ($do_debug) print "<div>" . $res->getUserInfo() ."</div>";
   } else {
      ?><script type="text/javascript">ref.aversion = <?=$assessments->getVersion()?></script><?
   }
}

?>
<script type="text/javascript">
   ref.setSavingMessage("Done");
   if (ref.saveForm) {
      ref.saved();
   }
   ref.document.getElementById('saving_div').style.visibility = 'hidden'
</script>

</body>
</html>

