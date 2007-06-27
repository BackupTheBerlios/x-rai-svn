<?
/*
    trash.php
    Deletes a pool
    
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
   $res = $xrai_db->query("UPDATE $db_filestatus SET version=1, status=0 WHERE idpool=? AND idfile=? AND inpool",array($idpool, $idfile));
   if (DB::isError($res)) break;
   $res = $xrai_db->query("DELETE FROM $db_filestatus WHERE idpool=? AND idfile=? AND not(inpool)",array($idpool,$idfile));
   if (DB::isError($res)) break;
   $res = $xrai_db->autoExecute($db_log, array("idpool" => $idpool, "idfile" => $idfile, "action" => "ERASE", "time" => $_REQUEST["time"]));

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