<?php
/*
    assess.php
    When the user saves its assessments, this script is called
    Ensures everything goes well and then notify the main window assessments
    have been saved
    
    Copyright (C) 2003-2007  Benjamin Piwowarski benjamin@bpiwowar.net

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Library General Public
    License as published by the Free Software Foundation; either
    version 2 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Library General Public License for more details.

    You should have received a copy of the GNU Library General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
*/

require_once("include/xrai.inc");
require_once("include/assessments.inc");
ignore_user_abort(false);

$file=stripcslashes($_REQUEST["file"]);
$collection=$_REQUEST["collection"];
$aversion=&$_REQUEST["aversion"];
$toadd=&$_REQUEST["a"];
$toremove=&$_REQUEST["r"];
$bep=&$_REQUEST["BEP"];
$id_pool=&$_REQUEST["id_pool"];
$docstatus=&$_REQUEST["docstatus"];
$hasrelevant=&$_REQUEST["hasrelevant"];
$hist=&$_REQUEST["hist"];

?>
<html>
<head><title>Assessment</title></head>
<body>
<script type="text/javascript">
   var ref = window.parent ? window.parent : window.opener;
   ref.setSavingMessage("Connected");
</script>

<div style="background: #eeeeef; padding: 3pt">
<div><b>Assessing</b></div>
<div><b>Collection:</b> <?="$collection"?></div>
<div><b>File:</b> <?="$file"?></div>
<div><b>Base version:</b> <?="$aversion"?></div>
<div><b>Doc status:</b> <?="$docstatus"?></div>
<div><b>Stats:</b> <?=sizeof($toadd)?> element(s) to add/modify, <?=sizeof($toremove)?> to remove.</div>
<?
   if (!$write_access) {
      ?><script type="text/javascript">
            ref.Message.show("warning","Pool is read-only.");
            ref.XRai.saved(false);
      </script><?
      exit();
   }
?>
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
   for($aaaa = true; $aaaa; $aaaa = false) { // useful for breaking the process without exceptions
      $res = $assessments->setNextVersion();
      if (DB::isError($res)) break;
      $res = $assessments->setStatus($docstatus);
      if (DB::isError($res)) break;
      $res = $assessments->setHasRelevant($hasrelevant);
      if ($do_debug) print "<div>Relevance status: " . ($hasrelevant ? "true" : "false") . "</div>";
      if (DB::isError($res)) break;
      
      if ($do_debug) print "<div>BEP is $bep</div>";
      $res = $assessments->setBEP($bep);
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
            $res = $xrai_db->autoExecute($db_log, array("idpool" => $assessments->idPool, "idfile" => $assessments->idFile,
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
