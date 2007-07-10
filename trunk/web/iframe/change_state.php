<?
/*
    change_state.php
    Change the state of a pool (locked - unlocked)
    
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