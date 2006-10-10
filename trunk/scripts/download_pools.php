<?
// kate: indent-mode cstyle
/*
   This script generates one assessment file by pool
   (c) B. Piwowarski, 2004

   Changes:
   - september 2006: handles BEP
   - october 2005: adaptation to the new highlighting method


   Example of call:

   export DIR=adhoc-2006; rm -rf ~/temp/$DIR; mkdir ~/temp/$DIR; php -d memory_limit=128M ~/2006/adhoc/xrai/scripts/download_pools.php  official ~/temp/$DIR > ~/temp/$DIR.log 2>&1 && ((cd ~/temp; tar c $DIR) | gzip -c > adhoc-$(date +%Y%m%d-%S%M%H).tgz); rm -rf ~/temp/$DIR
*/


$remote=true;
ignore_user_abort(false);
set_time_limit(0);
$old = getcwd();
chdir(dirname(__FILE__) . "/..");
$_SERVER["REMOTE_USER"] = "root";
require_once("include/xrai.inc");
require_once("include/assessments.inc");
require_once("include/xslt.inc");
chdir($old);

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
$done = array();

// First loop: get the list of files to explore
print "Getting the list of files\n";
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
      if (!is_array($done[$row["idfile"]])) 
         $done[$row["idfile"]] = array($row["collection"],$row["filename"],array());
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
   2. parent path, 
   3. offset in the parent path

  The stack $stack is composed of an array whose components are:
   0. path, 
   1. [ ranks (ie mapping tag name => integer) ], 
   2. start offset, 
   3. boolean (ancestor of an included node), 
   4. start offset of the last xrai:s tag (or -1 if the last parsed child is not xrai:s), 
   5. # of non-contiguous xrai:s tags, 6. BEP]
*/

function startElement($parser, $name, $attrs) {
   global $stack, $pos, $paths, $nb_passages, $topicPassages, $highlight_only, $debug;
   $last = &$stack[sizeof($stack)-1];

   $rank = &$last[1][$name];
   if (!isset($rank)) $rank = 1; else $rank++;
   if ($name == "xrai:s") {
      if ($last[4] == -1) { $last[4] = $pos; $last[5]++; }
   } else $last[4] = -1;

   $path = $last[0] . "/{$name}[{$rank}]";
   if ($highlight_only && is_array($topicPassages[$path])) {
      $x = &$topicPassages[$path][0]; // a passage (the fifth element (idx = 4) is the pool id
//       print "Size of starting passages is " . sizeof($x) . "\n";
      for($k = 0; $k < sizeof($x); $k++) {
         $nb_passages[$x[$k][4]]++;
         if ($nb_passages[$x[$k][4]] > 1) print "WARNING: Number of passages is > 1 for topic " . $x[4][$k] . ": " . $nb_passages[$x[$k][4]] . "\n";

      }
   }

   array_push($stack,array($path, array(), $pos, false, -1, 0));
}

function endElement($parser, $name) {
   global $stack, $paths, $pos, $nb_passages, $topicPassages, $highlight_only, $j, $debug;

   $data = &$stack[sizeof($stack)-1];
   $path = $data[0];
   
   if ($data[3] || $paths[$path] || ($highlight_only && (sizeof($nb_passages) > 0)) ) {
      if ($debug) print "\t* Interesting path: $path\n";
      $i = sizeof($stack)-1;
      while (($i>0) /*&& (!$stack[$i][3])*/) {
         if ($debug) print "\tLooking at " . $stack[$i][0] . "\n";
         if ($highlight_only) {
            // we add an assessed elements for all the pools within a passage
            foreach($nb_passages as $idpool => $count) {
//                print "\tEND PATH " . $stack[$i][0] . " for $idpool ($count)\n";
               if ($count > 0) {
                  if ($debug) print "\tADDING [$count passage(s)] for $idpool THE PATH " . $stack[$i][0] . "\n";
                  $j[$idpool][$stack[$i][0]] = 2;
               }
            }
         }
         $stack[$i][3] = true;
         $i--;
      }
      
      $paths[$path] = array($data[2], $pos);
      // Add the information about xrai:s if needed
      $last = &$stack[sizeof($stack)-2];
      if ($last[4] >= 0) { 
            // it is an xrai:s tag, we add an xpointer
            // print "ADDED a xrai:s: $path, $data[2]\n";
         array_push($paths[$path], "$last[0]/text()[$last[5]]", $last[4]);
      }
   }


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
   global $pos;
   $pos += strlen($data);
}

/** Return the XPointer for a given path
 @param path the XPath
 @param begin do we want an XPointer to the beginning of the element or to the end?
*/
function getXPointer($path, $begin) {
   global $paths;
   $p = &$paths[$path];
   if (sizeof($p) < 3) return $path;
   return "$p[2]." . ($p[$begin ? 0 : 1] - $p[3]);
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

print "Loop on files...\n";
reset($done);
// Loop on files:            
while (list($id, $data) = each(&$done)) {
   // Will contain the list of paths to analyse
   $paths = array();
   // start-end of passages for the highlight only assessments
   $topicPassages = array(); 
   // passages
   $p = array();
   // judgments 
   $j = array();
   
   print "\n[In $data[0]/$data[1] ($id)]\n";
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
      print "Parsing file $data[0]/$data[1] (" . sizeof($paths) . ")\n";
      $pos = 0;
      $nb_passages = array();
      $stack = array(array("",array(),0,false));
      $xml_parser = xml_parser_create();
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

   // Loop on pools
   if ($debug > 1) print "Info array is: " . print_r($p,true) . "\n";
   
   foreach($data[2] as &$data_item) {
      $pool = &$data_item[0];
      print "  > In pool $base_url/article?id_pool=$pool&collection=$data[0]&file=$data[1]\n";
      if ($debug > 1)  print "Current array: " .  print_r($p,true) . "\n";
      fwrite($files[$pool]," <file collection=\"$data[0]\" name=\"$data[1]\">\n");
      
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
            $s = $cp[$i][0][0]; $e =  $cp[$i][1][1];
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
            print "  P[$s:$e]\n"; // . " / $lp[2], $lp[3]]\n";
            $last = $e;
         }
         
         if ($debug > 1) print "Current array (2): " .  print_r($p,true) . "\n";
        // output the passages
        foreach($passages as $psg) {
                if ($debug) print "Output $psg[2] - $psg[3] (pool $pool)\n";
                 fwrite($files[$pool], "   <passage start=\"" . getXPointer($psg[2],true) . "\" end=\"" . getXPointer($psg[3],false) . "\" size=\"" . ($psg[1]-$psg[0]+1). "\"/>\n");
        }

         if ($debug > 1) print "Current array (3): " .  print_r($p,true) . "\n";
         // Output elements
         if ($j[$pool]) foreach($j[$pool] as $path => $exh) {
            $rsize = 0;
            $s = $paths[$path][0];
            $e = $paths[$path][1];
            foreach($passages as $seg) {
               if ($seg[0] > $e) break; // Stop if the start of the segment is after the end of the element
               $d = min($e,$seg[1]) - max($s,$seg[0]);
   //             print "$path, inter([$s:$e],[" . $cp[$i][0][0] . ":" . $cp[$i][1][1] . "]) = $d\n";
               if ($d >= 0) $rsize += $d + 1;
            }
            $error = false;
            if ($rsize <= 0 && ($s != $p)) {
               print "[[WARNING]] Specificity is null !?!\nfor $s:$e ($path) -> " ;
               foreach($passages as $seg) print "[$seg[0],$seg[1]]";
               print "\n";
               fwrite($files[$pool],"  <!-- Ignored assessment (null specificity): path: $path, exhaustivity: " . ($exh == -1 ? "?" : $exh) . "-->\n");
               continue;
            }
            $size = ($e - $s + 1);
            $spe = $rsize / $size;;
            if ($rsize > $size)
               die("Specificity is > 1 ($spe)!?!\nfor $s:$e ($path) -> " . print_r($passages,true) );

         if (!preg_match('#xrai:s#',$path))
            fwrite($files[$pool], "   <element path=\"$path\" exhaustivity=\"" . ($exh == -1 ? "?" : $exh) . "\" size=\"$size\" rsize=\"$rsize\"/>\n");
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
