#!/usr/bin/php
<?php

chdir("..");

require_once("include/xrai.inc");
require_once("include/assessments.inc");

if (sizeof($_SERVER["argv"]) != 6)
   die("addPool <state> <userid> <name> <default collection> <pool file>\n");

$poolstate = $_SERVER["argv"][1];
$userid = $_SERVER["argv"][2];
$poolname = $_SERVER["argv"][3];

$collection=$_SERVER["argv"][4];
$filename=$_SERVER["argv"][5];
if (!is_file($filename))
   die("'$filename' is not a file\n");

print "Starting processing of pool file '$filename'\n";



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
   global $id, $file, $xrai_db, $db_filestatus, $db_pools, $poolid, $poolstate, $userid, $poolname;
   if ($name == "pool") {
      $id = $attrs["topic"];
      if (!$id) die("No topic id defined!\n");
      $poolid = $xrai_db->nextId("{$db_pools}_id");
      if (DB::isError($poolid)) die($poolid->getUserInfo() . "\n");
      $res = $xrai_db->autoExecute("$db_pools",array("id" => $poolid, "idtopic" => $id, "login" => $userid, "name" => $poolname, "state" => $poolstate, "enabled" => "t"));
      if (DB::isError($res)) die($res->getUserInfo() . "\n");
      return;
   }

   if (!$id) die("No topic id defined!\n");
   switch($name) {
      case "file":
         $file = getFileId($attrs["file"]);
         if ($file > 0) {
            $res = $xrai_db->autoExecute($db_filestatus, array("idfile" => $file, "idpool" => $poolid, "status" => "0", "inpool" => "t", "version" => 1));
            if (DB::isError($res)) die($res->getUserInfo() . "\n");
         }
         break;
      case "path":
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
print "Done.\n";
?>