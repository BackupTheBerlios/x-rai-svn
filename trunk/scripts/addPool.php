#!/usr/bin/php
<?php
/*
    addPool.php
    Add a pool for a given topic and user id
    
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

require_once("xrai-inc.php");


$argv = &$_SERVER["argv"];
$poolid = 0;
$i = 0;
$skipdone=false;

while ($i + 1 < sizeof($argv)) {
   switch($argv[$i+1]) {
      // skip when (login,topic) is already there
      case "-skip-done": $skipdone=true; $i++; break;
      case "-update": $poolid = $argv[$i+2]; print "NOTICE: Updating pool $poolid\n"; $i += 2; break;
      default: break 2;
   }
}


if (sizeof($_SERVER["argv"]) - $i != 6)
   die("addPool [-update <poolid>] <state> <userid> <name> <default collection> <pool file>\n");

$poolstate = $_SERVER["argv"][1+$i];
$userid = $_SERVER["argv"][2+$i];
$poolname = $_SERVER["argv"][3+$i];

$collection=$_SERVER["argv"][4+$i];
$filename=$_SERVER["argv"][5+$i];
if (!is_file($filename))
   die("'$filename' is not a file\n");


$xrai_db->autoCommit(false);
print "Starting processing of pool file '$filename'\n";

$emptypathid = Paths::getPathId("", true);

// ==================================================
// Parse of file

function getFileId($path) {
   global $collection;
   $idFile = Files::getFileId($collection, $path, false);
   if (DB::isError($idFile)) { die($idFile->getUserInfo()  . "\n"); }
   if ($idFile < 0) { print "File '$collection, $path' id is < 0\n"; }
   return $idFile;
}

$file = -1;

function startElement($parser, $name, $attrs) {
   global $id, $file, $emptypathid, $xrai_db, $db_filestatus, $db_pools, $poolid, $poolstate, $userid, $poolname;
   if ($name == "pool") {
      $id = $attrs["topic"];
      if (!$id) die("No topic id defined!\n");
      
      global  $skipdone;
      if ($skipdone) {
         $res = $xrai_db->getOne("SELECT count(*) FROM $db_pools WHERE idtopic=? AND login=?",array($id,$userid));
         if (DB::isError($res)) { print "Error while reading pool state: " . dbMessage($res) . "\n"; exit(1); }
         if ($res > 0) { print "Skipping since there is already $res pool(s) corresponding to $userid/$id\n"; exit(0); }
      }

      if (!$poolid) {
         $poolid = $xrai_db->nextId("{$db_pools}_id");
         if (DB::isError($poolid)) die($poolid->getUserInfo() . "\n");
         $res = $xrai_db->autoExecute("$db_pools",array("id" => $poolid, "idtopic" => $id, "login" => $userid, "name" => $poolname, "state" => $poolstate, "enabled" => "t"));
         if (DB::isError($res)) die($res->getUserInfo() . "\n");
	 print "Info: pool id is $poolid\n";
      }
      return;
   }

   if (!$id) die("No topic id defined!\n");
   switch($name) {
      case "file":
         $file = getFileId($attrs["file"]);
         if ($file > 0) {
            $res = $xrai_db->getOne("SELECT count(*) FROM $db_filestatus WHERE idfile=? AND idpool=?",array($file,$poolid));
            if (DB::isError($res)) die($res->getUserInfo() . "\n");
            if ($res > 0) { print "/!\\ Skipping $file ($attrs[file])\n"; break; }
            print "Adding $file ($attrs[file])\n";
            $res = $xrai_db->autoExecute($db_filestatus, array("idfile" => $file, "idpool" => $poolid, "status" => "0", "inpool" => "t", "version" => 1, "bep" => $emptypathid));
            if (DB::isError($res)) die($res->getUserInfo() . "\n");
         }
         break;
      case "path":
      case "passage":   
         break;
      default: die("Unexpected tag: $name");
   }
}

function endElement($parser, $name) {
}

function cdata($parser, $data) {
}



// Parse


$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "cdata");
xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,false);

if (!($fp = fopen("$filename", "r")))
   die("could not open XML input");

while ($data = fread($fp, 4096)) {
   if (!xml_parse($xml_parser, $data, feof($fp))) {
      die(sprintf("XML error: %s at line %d",
      xml_error_string(xml_get_error_code($xml_parser)),
      xml_get_current_line_number($xml_parser)));
   }
}
xml_parser_free($xml_parser);

$res = $xrai_db->commit();
if (DB::isError($res)) print "Error while committing: " . $res->getUserInfo() . "\n";
print "Done.\n";
?>
