#!/usr/bin/php
<?php

$nopaths = false;

$i = 1;
while ($i < sizeof($_SERVER["argv"])) {
   switch($_SERVER["argv"][$i]) {
      case "-nopaths": print "/!\\ No paths mode\n"; $nopaths = 1; $i++; break;
      default: break 2;
   }
}

$collection = $_SERVER["argv"][$i];
$title=$_SERVER["argv"][$i+1];
$basedir=realpath($_SERVER["argv"][$i+2]);
chdir("..");

require_once("include/xrai.inc");
require_once("include/assessments.inc");

if (sizeof($_SERVER["argv"])-$i != 3)
   die("xrai2sql [-nopaths] <collection id> <collection title> <collection base dir>\n");
if (!is_dir($basedir))
   die("'$basedir' is not a directory\n");

print "Starting processing of collection '$collection' rooted at '$basedir'\n";

// ==================================================
// Misc

function get_name($a) {
  global $basedir;
  $r = preg_replace(array("#^$basedir(\/|$)#", '#\.\w+$#'), array('',''),$a);
//   print "$basedir # $a # $r\n";
  return $r;
}

// Stack of files
$files = array();

// To count the rank number
$rankcounts = array();

// Paths
$paths = array();
$knownpaths = array();

// ==================================================
// Parse of XML collection files

function startElementXML($parser, $name, $attrs) {
   global $currentdir, $files, $paths, $rankcounts, $knownpaths;
   // Get the rank
   $depth = sizeof($rankcounts);
   $ranks = &$rankcounts[$depth-1];
   $rank = $ranks[$name] = $ranks[$name] + 1;
   $path = $paths[$depth-1] . "/{$name}[$rank]";
   if (!$knownpaths[$path]) {
      Paths::getPathId($path, true);
   }
   array_push($paths, $path);
   array_push($rankcounts, array());
}

function endElementXML($parser, $name) {
   global $currentdir, $files, $paths, $rankcounts;
   array_pop($paths);
   array_pop($rankcounts);
}

function parseXML($file) {
   $xml_parser = xml_parser_create();
   xml_set_element_handler($xml_parser, "startElementXML", "endElementXML");
//    xml_set_character_data_handler($xml_parser, "cdata");
   xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,false);
   if (!($fp = fopen("$file", "r")))
      die("could not open XML input");
   while ($data = fread($fp, 4096)) {
      if (!xml_parse($xml_parser, $data, feof($fp))) {
         die(sprintf("XML error: %s at line %d",
                     xml_error_string(xml_get_error_code($xml_parser)),
                     xml_get_current_line_number($xml_parser)));
      }
   }
   xml_parser_free($xml_parser);
}

// ==================================================
// Parse of other files
function getFileId($path) {
   global $collection;
   $idFile = Files::getFileId($collection, $path, true);
   if (DB::isError($idFile)) { die($idFile->getUserInfo()  . "\n"); }
   if ($idFile < 0) { die("File id is < 0\n"); }
   return $idFile;
}

$status = 0; // 1 when in "subcollection" or "document"
$pre = 0; // pre-order index

function startElement($parser, $name, $attrs) {
   global $nopaths, $currentdir, $files, $paths, $rankcounts, $status, $pre;
   switch($name) {
      case "subcollection":
         $pre++;
         $olddir = $currentdir;
         $path = "$currentdir/$attrs[path]";
          if (!is_file($path)) {
            if (is_dir("$path")) { $path .= "/index.xrai"; }
          }
          if (!is_file($path)) die("Can't find subcollection with path $path (search for $path{.xrai,index,})\n");
          $currentdir = dirname($path);
          print "Parsing $path\n";
          $name = get_name("$olddir/$attrs[path]");
          array_push($files, array("id" => getFileId($name), "title" => "", "path" => $name, "type" => "xrai", "pre" => $pre));
          parse($path);
          $currentdir = $olddir;
          $status = 1;
         break;
      case "document":
          $pre++;
          $path = "$currentdir/$attrs[path].xml";
          print "Parsing $path\n";
          array_push($rankcounts,array());
          array_push($paths,"");
          $name = get_name("$path");
          array_push($files, array("id" => getFileId($name), "title" => "", "path" => $name, "type" => "xml", "pre" => $pre));
          if (!$nopaths) parseXML($path);
          array_pop($rankcounts);
          array_pop($paths);
          $status = 1;
          break;
      default:
       $status = 0;
    }

}

function endElement($parser, $name) {
   global $files, $pre, $xrai_db, $db_files, $collection;
   if ($name == "subcollection" || $name == "document") {
      $file = array_pop($files);
      $parent = $files[sizeof($files)-1]["id"];
      $file["title"] = preg_replace(array("/^\s+/","/\s+$/"),array("",""),$file["title"]);
      print "Updating $file[path] with id=$file[id] ($file[title]) to ($file[pre],$pre)\n";
      $res = $xrai_db->autoExecute($db_files, array("title" => $file["title"], "type" => $file["type"], "parent" => $parent, "pre" => $file["pre"], "post" => $pre),DB_AUTOQUERY_UPDATE,"id=$file[id]");
      if (DB::isError($res)) die($res->getUserInfo() . "\n");
//       print_r($file);
   }
   $status = 0;
}

function cdata($parser, $data) {
   global $status, $files;
   if ($status) {
      $data = preg_replace('#\s+#',' ',$data);
      $files[sizeof($files)-1]["title"] .= $data;
   }
}



function parse($xraifile) {
   $xml_parser = xml_parser_create();
   xml_set_element_handler($xml_parser, "startElement", "endElement");
   xml_set_character_data_handler($xml_parser, "cdata");
   xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,false);
   if (!($fp = fopen("$xraifile", "r")))
      die("could not open XML input");
   while ($data = fread($fp, 4096)) {
      if (!xml_parse($xml_parser, $data, feof($fp))) {
         die(sprintf("XML error: %s at line %d",
                     xml_error_string(xml_get_error_code($xml_parser)),
                     xml_get_current_line_number($xml_parser)));
      }
   }
   xml_parser_free($xml_parser);
}

$currentdir=$basedir;
print "Processing $xrai\n";
startElement(null,"subcollection",array());
cdata(null,$title);
endElement(null,"subcollection");

?>