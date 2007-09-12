<?
/*
    download_pools.php
    Generates the assessment files
    
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
   


   Changes:
   - september 2006: handles BEP
   - october 2005: adaptation to the new highlighting method


   Example of call:

   export DIR=adhoc-2006; rm -rf ~/temp/$DIR; mkdir ~/temp/$DIR; php -d memory_limit=128M ~/2006/adhoc/xrai/scripts/download_pools.php  -map wikien wikipedia official ~/temp/$DIR > ~/temp/$DIR.log 2>&1 && ((cd ~/temp; tar c $DIR) | gzip -c > adhoc-$(date +%Y%m%d-%S%M%H).tgz); rm -rf ~/temp/$DIR
*/

// kate: indent-mode cstyle


$remote=true;
ignore_user_abort(false);
set_time_limit(0);

require_once("xrai-inc.php");

$debug = 0;

define('ASSESSED',1);
define('P_START',2);
define('P_END',3);

function argerror($s="") {
         print "$s";
        print "download_pools <state> <outdir> [<restriction list>]\n";
        exit(1);
}

array_shift($argv);

$cmap = array();
$flag = true;

// do we strip whitespaces?
$strip_ws = false;

// do we merge whitespaces?
$strip_ws_pos = false;

// print "ARG0: $argv[0]\n";
while ($flag) {
   switch ($argv[0]) {
      case "-no-whitespace-nodes":
         array_shift($argv);
         $strip_ws = true;
         break;
      case "-merge-whitespaces":
         array_shift($argv);
         $strip_ws_pos = true;
         break;
      case "-cmap":
         array_shift($argv);
         $x = array_shift($argv);
         $y = array_shift($argv);
         $cmap[$x] = $y;
         print "Mapping $x to $y\n";
         break;
      case "-d":
      case "--debug":
	$debug++;
	break;
      default:
         $flag = false;
   }
}

$state = array_shift($argv); if ($state == null) argerror("No state found\n");
$outdir = array_shift($argv); if ($outdir == null) argerror("No output directory given\n");
if (!is_dir($outdir)) {
  print "$outdir is not a directory\n";
  exit(1);
}

$restrict="";
if (sizeof($argv) > 0) {
   $restrict = " AND idpool in (" . implode(",",$argv) . ") ";
}
print "Restrict to pools: " . implode(",",$argv) . "\n";

// DTD file

function write_dtd($subdir) {
global $outdir;
$dtd_file = fopen("$outdir/$subdir/assessments.dtd","w");
fwrite($dtd_file,'
<!--
      INEX 2005 Assessments DTD

      The DTD has two parts:
         (1) the topic definition (so as to have a complete file)
         (2) the assessments
  -->

<!ELEMENT assessments (inex_topic, file*)>

<!--
         From topics DTD
                              -->

<!ELEMENT inex_topic  (InitialTopicStatement, title,castitle?,parent?,description,narrative,keywords?)>
<!ELEMENT InitialTopicStatement (#PCDATA)>
<!ELEMENT keywords (#PCDATA)>
<!ATTLIST inex_topic
  topic_id     CDATA     #REQUIRED
  query_type  CDATA #REQUIRED
  ct_no      CDATA         #REQUIRED
>
<!ELEMENT title (#PCDATA)>
<!ELEMENT castitle     (#PCDATA)>
<!ELEMENT parent      (#PCDATA)>
<!ELEMENT description   (#PCDATA)>
<!ELEMENT narrative     (#PCDATA)>

<!--
         The assessments
                              -->


  <!ELEMENT file ((best-entry-point, passage+, element+)?)>
  <!ELEMENT passage EMPTY>
  <!ATTLIST passage start CDATA #REQUIRED end CDATA #REQUIRED size CDATA #REQUIRED>

  <!ELEMENT best-entry-point EMPTY>
  <!ATTLIST best-entry-point path CDATA #REQUIRED>

  <!ELEMENT element EMPTY>

  <!ATTLIST assessments
            (whitespace-text-node="' . ($strip_ws ? "strip-ALPHA" : "keep-ALPHA") .' "
            pool CDATA #REQUIRED
            topic CDATA #REQUIRED
            version CDATA #REQUIRED>
  <!ATTLIST collection name CDATA #REQUIRED>
  <!ATTLIST file collection CDATA #REQUIRED name CDATA #REQUIRED>

  <!-- exhaustivity is 1 or 2, size is the size of the element (in characters) and rsize the # of highlighted chars within that element -->
  <!ATTLIST element
        path    CDATA   #REQUIRED
        exhaustivity   CDATA #REQUIRED
        size CDATA #REQUIRED
        rsize CDATA #REQUIRED
  >



  ');
fclose($dtd_file);
}

// -*- Write assessment files


@mkdir("$outdir/done"); // Directory with links to done pools
write_dtd("done");
@mkdir("$outdir/in_progress"); // Directory with links to done pools
write_dtd("in_progress");



$files = array();



// ========================================
// Selects all the files and begin to

// -*- Copy the file
function cp_startElement($parser, $name, $attrs) {
   global $cdef;
   $cdef .= "<$name";
   foreach($attrs as $k => $v) $cdef .= " $k=\"$v\"";
   $cdef .= ">";
}

function cp_endElement($parser, $name) {
   global $cdef;
   $cdef .= "</$name>";
}

function cp_cdata($parser, $data) {
   global $cdef;
   $cdef .= $data;
}

// $data = (idpool, idtopic, alldone, definition)
function addPool(&$data) {
   global $outdir, $files, $db_true;
   if (isset($files[$data[0]])) exit("ERROR: file $data[0] is already opened!\n");
   $dir = "$outdir/" . ($data[2] ? "done" : "in_progress") . "/$data[4]";
   if (!is_dir($dir)) mkdir($dir);
   $dir = "$dir/" . ($data[5] ? "main" : "others");
   if (!is_dir($dir)) mkdir($dir);
   $dir = "$dir" . "/topic-$data[1]";
   if (!is_dir($dir)) mkdir($dir);
   $fh = $files[$data[0]] = fopen("$dir/pool-$data[0].xml","w");
   fwrite($fh, "<?xml version=\"1.0\"?>\n<!DOCTYPE assessments SYSTEM \"../../assessments.dtd\">\n<assessments pool=\"$data[0]\" topic=\"$data[1]\" version=\"2\">\n\n<!-- Topic definition -->\n" . $data[3] . "\n\n<!-- Topic assessments (only completed files) -->\n\n");
}

// Select the pools for a given file
$status = $xrai_db->query("SELECT idpool, idtopic, status, idfile, collection, filename, topics.type, main, paths.path as bep FROM filestatus, pools, files, topics, paths WHERE topics.id = pools.idtopic AND pools.id = idpool AND files.id = idfile AND state=? $restrict AND paths.id=bep ORDER BY idpool",array($state));
if (DB::isError($status)) { print "Error.\n" . $status->getUserInfo() . "\n\nExiting\n"; exit(1); }

$current = array(null);
$alldone = true;
// True if the current text node is only made of white spaces
$only_ws = true;
$done = array();

// First loop: get the list of files to explore
print "Getting the list of " . $status->numRows() . "files...\n";
$numberOfFiles = 0;
while ($row = $status->fetchRow(DB_FETCHMODE_ASSOC)) {
      
   // New topic?
   if ($current[0] != $row["idpool"]) {
      if ($current[0] != null) addPool(&$current);
      $current = array($row["idpool"],$row["idtopic"],true,null,$row["type"],$row["main"]  == $db_true);
      $def = $xrai_db->getOne("SELECT definition FROM $db_topics WHERE id=?",array($row["idtopic"]));
      if (DB::isError($def)) {
         print "Topic definion cannot be retrieved: " . $def->getUserInfo() . "\n";
         exit(1);
      }
      $cdef = &$current[3];
         
      // Parse the topic and output it to the file
      $xml_parser_cp = xml_parser_create();
      xml_set_element_handler($xml_parser_cp, "cp_startElement", "cp_endElement");
      xml_set_character_data_handler($xml_parser_cp, "cp_cdata");
      xml_parser_set_option($xml_parser_cp,XML_OPTION_CASE_FOLDING,false);
      xml_parser_set_option($xml_parser_cp,XML_OPTION_SKIP_WHITE,false);
      if (!xml_parse($xml_parser_cp, $def, true)) {
         die(sprintf("(1) XML error while parsing definition\n\n$def\n\n: %s at line %d",
                     xml_error_string(xml_get_error_code($xml_parser_cp)),
                     xml_get_current_line_number($xml_parser_cp)));
      }
      xml_parser_free($xml_parser_cp);
   } 
            
   // Get the filename and the status
   // "done" is an array topic id =>
   // [ collection, filename, list of arrays [pool id, bep] ]
   if ($row["status"] != 2) $current[2] = false;
   else {
      if (!is_array($done[$row["idfile"]])) {
         // We have a new file
         $numberOfFiles++;
         $done[$row["idfile"]] = array($row["collection"],$row["filename"],array());
      }
      // Add this pool id for this file id
      if ($row["bep"] == "null" || $row["bep"] == "") $row["bep"] = false;
      array_push($done[$row["idfile"]][2],array($row["idpool"],$row["bep"]));
   }
}

addPool(&$current);

// ========================================
// Loop on files


/* ----*---- Read the XML file ----*----

 fill a global data array ($paths) only for paths already in the set of keys $paths (and their ancestors):
   0. start offset, 
   1. end offset
 If the element is xrai:s, added to the array are:
   2. parent path (i.e. a text node), 
   3. offset in the parent path

  The stack $stack is composed of an array whose components are:
   0. path, 
   1. [ ranks (ie mapping tag name => integer) ], 
   2. start offset, 
   3. boolean (ancestor of an included node), 
   4. start offset of the last xrai:s tag (or -1 if the last parsed child is not xrai:s), 
   5. # of non-contiguous xrai:s tags
   6. BEP
*/

// this contains the last path we parsed
$last_path = null;
// this contains a reference to the array where we need next path information
$need_next = null;

class Path {

// Counting ALL whitespaces
const START_OFFSET = 0;
const END_OFFSET = 1;

// Not counting adjacent whitespaces
const MWS_START_OFFSET = 2;
const MWS_END_OFFSET = 3;

const PARENT_PATH = 4;
// IF PARENT PATH IS NOT 0
const PARENT_OFFSET = 5;
// IF PARENT PATH IS 0
const PREVIOUS_PATH = 5;
const NEXT_PATH = 6;
}   

// Stack info
class Stack {
   const PATH = 0;
   const RANKS = 1;
   
   const START_OFFSET = 2;
   const ANCESTOR_RELEVANT = 3;
   
   // OFFSET OF THE START POINT OF THE FIRST XRAI_S ELEMENT
   const START_XRAI_S = 4;
   const NB_XRAIS = 5;
   
   // OFFSET WITH MERGED WHITESPACES
   const MWS_START_OFFSET = 6;
}
   
function startElement($parser, $name, $attrs) {
   global $stack, $pos, $pos_mws, $paths, $nb_passages, $topicPassages, $highlight_only, $debug, $only_ws;

   // Update the current element
   if (sizeof($stack) == 1) { $last_was_ws = false; }
   $last = &$stack[sizeof($stack)-1];

   $rank = &$last[Stack::RANKS][$name];
   if (!isset($rank)) $rank = 1; else $rank++;

   $path = $last[0] . "/{$name}[{$rank}]";
   if ($highlight_only && is_array($topicPassages[$path])) {
      $x = &$topicPassages[$path][0]; // a passage (the fifth element (idx = 4) is the pool id
//       print "Size of starting passages is " . sizeof($x) . "\n";
      for($k = 0; $k < sizeof($x); $k++) {
         $nb_passages[$x[$k][4]]++;
         if ($nb_passages[$x[$k][4]] > 1) print "WARNING: Number of passages is > 1 for topic " . $x[4][$k] . ": " . $nb_passages[$x[$k][4]] . "\n";

      }
   }
      
   // For the moment, only made of white spaces  
   $only_ws = true;
   
   // -- Push a new element in the array for the current element
   
   array_push($stack,array($path, array(), $pos, false, -1, 0, $pos_mws));
}

function endElement($parser, $name) {
   global $stack, $paths, $pos, $pos_mws, $nb_passages, $topicPassages, $highlight_only, $j, $only_ws,
          $strip_ws, $debug, $last_path, $need_next;

   // Update information about the parent of the current element
   $last = &$stack[sizeof($stack)-2];
   $data = &$stack[sizeof($stack)-1];

   if ($last) {
      // if the element was an xrai:s element, only count it as a white space node
      // if (1) we don't care about stripping ws only nodes or
      // (2) it did not contain only whitespaces
      if ($name == "xrai:s") {
         $x = $last[Stack::START_XRAI_S];
         if (!$strip_ws || !$only_ws) {
            // This is the first xrai_s of this part
             if ($x < 0) { 
                  // Offset is parent offset
                  // remove 1 to the offset if the previous node was an empty ws and strip_ws is on
                  // in order to count this for the offset
                  $last[Stack::START_XRAI_S] = $x == -1 ? $data[Stack::START_OFFSET] :  -2 -$x; 
                  $last[Stack::NB_XRAIS]++; 
               }
          } else if ($only_ws && $x == -1) {
             // Only ws but strip ws, then skip
             $last[Stack::START_XRAI_S] = -$data[Stack::START_OFFSET]-2; 
          } 
        
       } else {
          // Reset to -1 since we are not an xrai:s element
          $last[Stack::START_XRAI_S] = -1;
       }
    }
      
   // Process information   
   $path = $data[Stack::PATH];
   
   // Check if we needed our information for handling special case of xrai:s tags
   

   if ($need_next) {
      if ($name != "xrai:s") {
         array_push($need_next, $path);
         unset($GLOBALS["need_next"]);
      } else  if ($last[Stack::START_XRAI_S] >= 0) {
           array_push($need_next, $last[Stack::PATH] . "/text()[" . $last[Stack::NB_XRAIS] . "]."  
                 . ($last[Stack::START_XRAI_S] - $data[Stack::START_OFFSET]));
         unset($GLOBALS["need_next"]);
      }
   }

   if ($data[Stack::ANCESTOR_RELEVANT] || $paths[$path] || ($highlight_only && (sizeof($nb_passages) > 0)) ) {
      if ($debug) print "\t* Interesting path: $path\n";
      $i = sizeof($stack)-1;
      while (($i>0) /*&& (!$stack[$i][3])*/) {
         if ($debug) print "\tLooking at " . $stack[$i][Stack::PATH] . "\n";
      
         if ($highlight_only) {
            // we add an assessed elements for all the pools within a passage
            foreach($nb_passages as $idpool => $count) {
//                print "\tEND PATH " . $stack[$i][0] . " for $idpool ($count)\n";
               if ($count > 0) {
                  if ($debug) print "\tADDING [$count passage(s)] for $idpool THE PATH " . $stack[$i][0] . "\n";
                  $j[$idpool][$stack[$i][Stack::PATH]] = 2;
               }
            }
         }
         $stack[$i][Stack::ANCESTOR_RELEVANT] = true;
         $i--;
      }
      
      $paths[$path] = array($data[Stack::START_OFFSET], $pos, $data[Stack::MWS_START_OFFSET], $pos_mws);
      
      // Add the information about xrai:s if needed
      if ($name == "xrai:s") {
         if ($last[Stack::START_XRAI_S] >= 0) { 
               // it is an xrai:s tag, we add an xpointer
               // print "ADDED a xrai:s: $path, $data[2]\n";
            array_push($paths[$path], $last[Stack::PATH] . "/text()[" . $last[Stack::NB_XRAIS] . "]", $last[Stack::START_XRAI_S]);
         } else {
            // Case where we are within an empty text node and skip_ws is on
            // only choice: put the next sibling (for last part) & previous sibling (for previous part) reference
            // TODO !!!
            array_push($paths[$path], 0, $last_path);
            $GLOBALS["need_next"] = &$paths[$path];
         }
      }
   }
   $last_path = $path;

   array_pop($stack);
   
   
   
   // Remove passages
   if ($highlight_only && is_array($topicPassages[$path])) {
      $x = &$topicPassages[$path][1]; // a passage (the fifth element (idx = 4) is the pool id
      for($k = 0; $k < sizeof($x); $k++) {
         $nb_passages[$x[$k][4]]--;
         if ($nb_passages[$x[$k][4]] < 0) 
            print "WARNING: Number of passages is below 0 for topic " . $x[$k][4] . ": " . $nb_passages[$x[$k][4]] . "\n";
         if ($nb_passages[$x[$k][4]] == 0) unset($nb_passages[$x[$k][4]]);
      }
   }

}

function cdata($parser, $data) {
   global $pos, $pos_mws, $only_ws, $last_was_ws;
   // Strip whitespaces from count
   $pos += strlen($data);
   
   $data = preg_replace("#\s+#"," ",$data);
   if ($last_was_ws && preg_match("#^\s#",$data)) $pos--;
   $last_was_ws = preg_match("#\s$#",$data);
   $pos_mws += strlen($data);
   
   
   $only_ws = $only_ws && !preg_match('#\S#', $data);
}

/** Return the XPointer for a given path
 @param path the XPath
 @param begin do we want an XPointer relative to the beginning of the element or to the end?
*/
function getXPointer($path, $begin) {
   global $paths;
   $p = &$paths[$path];
   if (sizeof($p) <= Path::PARENT_PATH) return $path;
   // An xrai:s node
   if ($p[Path::PARENT_PATH]) 
      return $p[Path::PARENT_PATH] . "." . ($p[$begin ? Path::START_OFFSET : Path::END_OFFSET] - $p[Path::PARENT_OFFSET]);
//    print "$path : " . $p[Path::NEXT_PATH] . ", " . $p[Path::PREVIOUS_PATH] . "\n";
   return ($begin ? $p[Path::NEXT_PATH] : $p[Path::PREVIOUS_PATH]);
}

function passageIsOnlyWS($spath, $epath) {
   global $paths;
   $p = &$paths[$spath];
   if (sizeof($p) <= Path::PARENT_PATH || $p[Path::PARENT_PATH]) return false;
   $q = &$paths[$epath];
   return sizeof($q) > Path::PARENT_PATH && ($q[Path::PARENT_PATH] == 0);
}

/* -----*----- Loop on files -----*-----
      
   The files id are the keys of the array $done. Within the loop:
      - $id is the topic id 
      - $data is an array:
           0. collection, 
           1. filename, 
           2. list of arrays [0. pool id, 1. bep]

*/
      
// current contains the current file / collection, the different paths, the passages and assessments
function psort_order($a,$b) {
   return $a[0][0] - $b[0][0];
}

$query = "SELECT assessments.exhaustivity, assessments.idpool, exhaustivity, pathsstart.\"path\" AS pstart, pathsend.\"path\" AS pend
   FROM assessments
   JOIN paths pathsstart ON assessments.startpath = pathsstart.id
   JOIN paths pathsend ON assessments.endpath = pathsend.id
   WHERE assessments.idfile = ? AND idpool in (";

// Implode an array of arrays using the $k component each time
function implode_array($sep, &$a, $k) {
   $s = "";
   for($i = 0; $i < sizeof($a); $i++)
      $s .= ($i > 0 ? $sep : "") . $a[$i][$k];
   return $s;
}

print "Loop on files (total = $numberOfFiles)...\n";
$fileNo = 0;
$currentPct = 0; // current percentage

reset($done);
// Loop on files:            
while (list($id, $data) = each(&$done)) {
   // Progress indicator
   $fileNo++;
   $pct = intval($fileNo / $numberOfFiles * 20) * 5;
   if ($pct != $currentPct) {
      $currentPct = $pct;
      print "\n---- PROGRESS ---- $pct %\n";
   }

   // Will contain the list of paths to analyse
   $paths = array();
   // start-end of passages for the highlight only assessments
   $topicPassages = array(); 
   // passages
   $p = array();
   // judgments 
   $j = array();
   
   if ($debug> 0) print "\n[In $data[0]/$data[1] ($id)]\n";
   // Should not happen !
   if (sizeof($data[2]) == 0) die();

   // Get the list of assessments for the pools
   $list = $xrai_db->query($query . implode_array(",", $data[2], 0) . ")", $id);
   if (DB::isError($list)) { print "Error.\n" . $list->getUserInfo() . "\n\nExiting\n"; exit(1); }
//    print "Query was: " . $xrai_db->last_query . ": " . $list->numRows() . "\n";
   // Add BEP paths
   for($k = 0; $k < sizeof($data[2]); $k++) 
      if ($data[2][$k][1]) {
         $paths[$data[2][$k][1]] = true;
         if ($debug) print " Added BEP (pool " . $data[2][$k][0] . ") => " . $data[2][$k][1] . "\n";
      }  
   while ($row = &$list->fetchRow(DB_FETCHMODE_ASSOC)) {
      $idpool = $row["idpool"];

      $paths[$row["pstart"]] = true;

      if ($row["pend"]) {
         // Passage
         if ($debug) print " Adding passage (pool $idpool) $row[pstart] - $row[pend]\n";
         if (!is_array($p[$idpool])) $p[$idpool] = array();
         $paths[$row["pend"]] = true;
         $p[$idpool][] = array(&$paths[$row["pstart"]], &$paths[$row["pend"]], $row["pstart"], $row["pend"], $idpool);

         if ($highlight_only) {
            // Adding this passage
            $psg = &$p[$idpool][sizeof($p[$idpool])-1];
            if (!isset($topicPassages[$row["pstart"]])) $topicPassages[$row["pstart"]] = array(array(), array());
            if (!isset($topicPassages[$row["pend"]])) $topicPassages[$row["pend"]] = array(array(),array());
            array_push($topicPassages[$row["pstart"]][0], &$psg);
            array_push($topicPassages[$row["pend"]][1], &$psg);
            unset($psg);
         }  

      } else {
         // Assessed element
         if ($debug) print " Adding assessed element (pool $idpool) $row[pstart]\n";
         $j[$idpool][$row["pstart"]] = ($e = $row["exhaustivity"]);
         // do some inference (exhaustivity for ancestors)
         $path = $row["pstart"];
         while ($path != "") {
            $path = preg_replace('#/[^/]+$#','',$path);
            if ($path == "") break;
            $paths[$path] = true;
            $cj = &$j[$idpool][$path];
            if (!isset($cj)) $cj = $e;
            else if ($e > $cj) $cj = $e;
            else break;
         }
      }
   }
   $list->free();

   // Read XML file (if necessary)
   if (sizeof($paths) > 0) {
      if ($debug) print "Parsing file $data[0]/$data[1] (" . sizeof($paths) . ")\n";
      $pos = $pos_mws = 0;
      $nb_passages = array();
      $stack = array(array("",array(),0,false));
      $xml_parser = xml_parser_create();
      $last_was_ws = false;
      xml_set_element_handler($xml_parser, "startElement", "endElement");
      xml_set_character_data_handler($xml_parser, "cdata");
      xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,false);
      xml_parser_set_option($xml_parser,XML_OPTION_SKIP_WHITE,false);
      if (function_exists("getArticle")) {
         $fp = getArticle("$data[0]","$data[1]");
      } else
         $fp = fopen("$xml_documents/$data[0]/$data[1].xml", "r");
      
      if (!$fp)
         die("could not open XML input");
      while ($chars = fread($fp, 4096)) {
         if (!xml_parse($xml_parser, $chars, feof($fp))) {
            die(sprintf("XML error ($xml_documents/$data[0]/$data[1].xml): %s at line %d, column %d in [$chars]\n",
                        xml_error_string(xml_get_error_code($xml_parser)),
                        xml_get_current_line_number($xml_parser),
                        xml_get_current_column_number($xml_parser)));
         }
      }
      xml_parser_free($xml_parser);
   }

   // --- Loop on pools
   if ($debug > 1) print "Info array is: " . print_r($p,true) . "\n";
   
   foreach($data[2] as &$data_item) {
      $pool = &$data_item[0];
      if ($debug > 0) print "  > In pool $base_url/article?id_pool=$pool&collection=$data[0]&file=$data[1]\n";
      if ($debug > 1)  print "Current array: " .  print_r($p,true) . "\n";
      $coll = $data[0];
      if ($cmap[$coll]) $coll = $cmap[$coll];
      fwrite($files[$pool]," <file collection=\"$coll\" name=\"$data[1]\">\n");
      
      if ($debug > 1) { print "BEP : $data_item[1] i.e. " . print_r($paths[$data_item[1]], true) . "\n"; }
      if ($data_item[1]) fwrite($files[$pool],"   <best-entry-point path=\"" . getXPointer($data_item[1], true) . "\"/>\n");
      $error = false;
      $cp = &$p[$pool];
      $passages = array();

      if (is_array($cp)) {

         // sort passages and remove overlapping passages (due to a bug in X-Rai)
         usort($cp,"psort_order");
         if ($debug > 1) print "Sorted array: " .  print_r($p,true) . "\n";
         $last = -1;
         for($i = 0; $i < sizeof($cp); $i++) {
            $s = $cp[$i][0][Path::MWS_START_OFFSET]; 
            $e =  $cp[$i][1][Path::MWS_END_OFFSET];
            
            if (!isset($s) || !isset($e)) { 
               print "WARNING: skipping passage " . $cp[$i][2] . " - " . $cp[$i][3] . " (start ($s)/end ($e) empty)\n"; 
               fwrite($files[$pool],"  <!-- WARNING: Skipping passage " . $cp[$i][2] . " - " . $cp[$i][3] . " (start ($s)/end ($e) empty)-->\n");
               continue; 
            }
            if ($s <= $last) {
               $lp = &$passages[sizeof($passages)-1];
               if ($s != $last) {
                  print "[[WARNING]] passage overlap ($s<=$last) - merging!\n";
                  fwrite($files[$pool],"  <!-- WARNING: passage overlap ($s<=$last) - merging -->\n");
               }
               if ($lp[1] >= $e) continue; // completly included in previous
               $error = true;
               $s = $lp[0];
               $lp[1] = $e;
               $lp[3] = $cp[$i][3];
            } else {
               $passages[] = array($s, $e, $cp[$i][2], $cp[$i][3]);
            }
//          $lp = &$passages[sizeof($passages)-1];
            if ($debug) print "  P[$s:$e]\n"; // . " / $lp[2], $lp[3]]\n";
            $last = $e;
         }
         
         if ($debug > 1) print "Current array (2): " .  print_r($p,true) . "\n";
        // output the passages
        foreach($passages as $psg) {
                if ($debug) print "Output $psg[2] - $psg[3] (pool $pool)\n";
/*                print "Passage $psg[2] to $psg[3] is " . (passageIsOnlyWS($psg[2],$psg[3]) ? "ONLY WS" : "OK") 
                      . " and offset $psg[0] to $psg[1]" . "\n";*/
                if ($strip_ws && passageIsOnlyWS($psg[2],$psg[3])) {
                    fwrite($files[$pool], "   <!-- removed whitespace only passage " . getXPointer($psg[2],true) . " to " . getXPointer($psg[3],false) . " of size " . ($psg[1]-$psg[0]). " -->\n");
                  continue;
                }
                 fwrite($files[$pool], "   <passage start=\"" . getXPointer($psg[2],true) . "\" end=\"" . getXPointer($psg[3],false) . "\" size=\"" . ($psg[1]-$psg[0]). "\"/>\n");
        }

         if ($debug > 1) print "Current array (3): " .  print_r($p,true) . "\n";
         // Output elements
         if ($j[$pool]) foreach($j[$pool] as $path => $exh) {
            $rsize = 0;
            $size = $e - $s; // +1 ???
            
            $s = $paths[$path][Path::MWS_START_OFFSET];
            $e = $paths[$path][Path::MWS_END_OFFSET];
            // Compute the intersection between segments and passages
            foreach($passages as $seg) {
               if ($seg[0] > $e) break; // Stop if the start of the segment is after the end of the element
               $d = min($e,$seg[1]) - max($s,$seg[0]);
                if ($debug) print "$path, inter([$s:$e],[" . $seg[0] . ":" . $seg[1] . "]) = $d\n";
               if ($d >= 0) $rsize += $d; // + 1;
            }
            $error = false;
            
            if ($rsize <= 0 && $size > 0) {
               print "[[WARNING]] Specificity is null !?!\nfor $s:$e ($path) : $rsize vs $size -> " ;
               foreach($passages as $seg) print "[$seg[0],$seg[1]]";
               if ($debug) print "\n";
               fwrite($files[$pool],"  <!-- Ignored assessment (null specificity): path: $path, exhaustivity: " . ($exh == -1 ? "?" : $exh) . "-->\n");
               continue;
            }
            
            if ($rsize > $size)
               die("Specificity is > 1 ($rsize)!?!\nfor $s:$e ($path) with passages " . print_r($passages,true) );

         if (!preg_match('#xrai:s#',$path)) {
            fwrite($files[$pool], "   <element path=\"$path\" exhaustivity=\"" . ($exh == -1 ? "?" : $exh) . "\" size=\"$size\" rsize=\"$rsize\"/>\n");

         }
         }
      }
      fwrite($files[$pool]," </file>\n");
}
}



// =========================================


foreach($files as $id => $fh) {
   fwrite($fh, "</assessments>\n");
   fclose($fh);
}

?>
