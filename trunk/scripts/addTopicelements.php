#!/usr/bin/php
<?php
/*
    addTopicelements.php
    Add the support elements (elements retrieved by search engines)
    
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

if (sizeof($_SERVER["argv"]) != 3)
   die("addPool <default collection> <pool file>\n");

$collection=$_SERVER["argv"][1];
$filename=$_SERVER["argv"][2];
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

$knownpaths = array();

function getPathId($path) {
   global $knownpaths;
   if (!$knownpaths[$path]) {
      $id = Paths::getPathId($path, false);
      if (DB::isError($id)) { die($id->getUserInfo()  . "\n"); }
      $knownpaths[$path] = $id;
   }
//    if ($id < 0) { die("File id is < 0\n"); }
   return $knownpaths[$path];
}

$file = -1;

function startElement($parser, $name, $attrs) {
   global $filepath, $id, $file, $xrai_db, $db_topicelements;
   if ($name == "pool") {
      $id = $attrs["topic"];
      if (!$id) die("No topic id defined!\n");
      $res = $xrai_db->query("DELETE FROM $db_topicelements WHERE idtopic=?",array($id));
      if (DB::isError($res)) die($res->getUserInfo() . "\n");
      return;
   }

   if (!$id) die("No topic id defined!\n");
   switch($name) {
      case "file":
         $filepath = $attrs["file"];
         $file = getFileId($attrs["file"]);
         break;
      case "path":
         $pathid = getPathId($attrs["path"]);
         if ($file > 0 && $pathid > 0) {
            print "Adding $file, $pathid ($filepath, $attrs[path])\n";
            $res = $xrai_db->autoExecute($db_topicelements, array("idfile" => $file, "idtopic" => $id, "idpath" => $pathid));
            if (DB::isError($res)) print "[ERROR: " . $res->getUserInfo() . "] Skipping $file, $pathid\n";
            // die($res->getUserInfo() . "\n");
         } else print "[ERROR] Skipping $file, $pathid\n";

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
