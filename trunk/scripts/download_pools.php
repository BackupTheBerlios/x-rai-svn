<?


/*
   This script generates one assessment file by pool
   (c) B. Piwowarski, 2004

   Changes:
   - october 2005: adaptation to the new highlighting method
*/


$remote=true;
ignore_user_abort(false);
set_time_limit(0);
chdir("..");
require_once("include/xrai.inc");
require_once("include/assessments.inc");
require_once("include/xslt.inc");

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


  <!ELEMENT file (element*)>
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
   global $outdir, $files;
   $dir = "$outdir/" . ($data[2] ? "done" : "in_progress") . "/topic-$data[1]";
   if (!is_dir($dir)) mkdir($dir);
   $fh = $files[$data[0]] = fopen("$dir/pool-$data[0].xml","w");
   fwrite($fh, "<?xml version=\"1.0\"?>\n<!DOCTYPE assessments SYSTEM \"../assessments.dtd\">\n<assessments pool=\"$data[0]\" topic=\"$data[1]\" version=\"2\">\n\n<!-- Topic definition -->\n" . $data[3] . "\n\n<!-- Topic assessments (only completed files) -->\n\n");
}

$status = $xrai_db->query("SELECT idpool, idtopic, status, idfile, collection, filename FROM filestatus, pools, files WHERE pools.id = idpool AND files.id = idfile AND state=? $restrict",array($state));
if (DB::isError($status)) { print "Error.\n" . $list->getUserInfo() . "\n\nExiting\n"; exit(1); }

$current = array(null);
$alldone = true;
$done = array();

while ($row = $status->fetchRow(DB_FETCHMODE_ASSOC)) {
   if ($current[0] != $row["idpool"]) {
      if ($current[0] != null) addPool(&$current);
      $current = array($row["idpool"],$row["idtopic"],true,null);
      $def = $xrai_db->getOne("SELECT definition FROM $db_topics WHERE id=?",array($row["idtopic"]));
      if (DB::isError($def)) {
         print "Topic definion cannot be retrieved: " . $def->getUserInfo() . "\n";
         exit(1);
      }
      $cdef = &$current[3];
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
   if ($row["status"] != 2) $current[2] = false;
   else {
      if (!is_array($done[$row["idfile"]])) $done[$row["idfile"]] = array($row["collection"],$row["filename"],array());
      array_push($done[$row["idfile"]][2],$row["idpool"]);
   }
}

addPool(&$current);

// ========================================
// Loop on files

// -*- Read the XML file
function startElement($parser, $name, $attrs) {
   global $stack, $pos;
   $last = &$stack[sizeof($stack)-1];
   $rank = &$last[1][$name];
   if (!isset($rank)) $rank = 1; else $rank++;
   $path = $last[0] . "/{$name}[{$rank}]";
   array_push($stack,array($path, array(), $pos, false));
}

function endElement($parser, $name) {
   global $stack, $paths, $pos;
   $data = array_pop($stack);
//    print "$data[0] has ($data[2],$pos)\n";
   if ($data[3] || ($paths[$data[0]]) ) {
      $i = sizeof($stack)-1;
      while (($i>0) && (!$stack[$i][3])) { $stack[$i][3] = true; $i--; }
      $paths[$data[0]] = array($data[2], $pos);
   }
}

function cdata($parser, $data) {
   global $pos;
   $pos += strlen($data);
}




// -*- Loop on files
// current contains the current file / collection, the different paths, the passages and assessments
function psort_order($a,$b) {
   return $a[0][0] - $b[0][0];
}

$query = "SELECT assessments.exhaustivity, assessments.idpool, exhaustivity, pathsstart.\"path\" AS pstart, pathsend.\"path\" AS pend
   FROM assessments
   JOIN paths pathsstart ON assessments.startpath = pathsstart.id
   JOIN paths pathsend ON assessments.endpath = pathsend.id
   WHERE assessments.idfile = ? AND idpool in (";

reset($done);
while (list($id, $data) = each(&$done)) {
   $paths = array();
   $p = array(); // passages
   $j = array(); // judgments
   print "[In $data[0]/$data[1] ($id)]\n";
   if (sizeof($data[2]) == 0) die();

   $list = $xrai_db->query($query . implode(",", $data[2]) . ")", $id);
   if (DB::isError($list)) { print "Error.\n" . $list->getUserInfo() . "\n\nExiting\n"; exit(1); }
   while ($row = &$list->fetchRow(DB_FETCHMODE_ASSOC)) {
      $idpool = $row["idpool"];
      if (!is_array($p[$idpool])) $p[$idpool] = array();
      $paths[$row["pstart"]] = true;
      if ($row["pend"]) {
         $paths[$row["pend"]] = true;
         $p[$idpool][] = array(&$paths[$row["pstart"]], &$paths[$row["pend"]]);
      }
      else {
         $j[$idpool][$row["pstart"]] = ($e = $row["exhaustivity"]);
         // do some inference
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
      $stack = array(array("",array(),0,false));
      $xml_parser = xml_parser_create();
      xml_set_element_handler($xml_parser, "startElement", "endElement");
      xml_set_character_data_handler($xml_parser, "cdata");
      xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,false);
      xml_parser_set_option($xml_parser,XML_OPTION_SKIP_WHITE,false);
      if (!($fp = fopen("$xml_documents/$data[0]/$data[1].xml", "r")))
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
   foreach($data[2] as $pool) {
      print "  > In pool $base_url/article?id_pool=$pool&collection=$data[0]&file=$data[1]\n";
      fwrite($files[$pool]," <file collection=\"$data[0]\" name=\"$data[1]\">\n");
      $error = false;
      $cp = &$p[$pool];
      if (is_array($cp)) {
         usort($cp,"psort_order");
         $passages = array();
         $last = -1;
         for($i = 0; $i < sizeof($cp); $i++) {
            $s = $cp[$i][0][0]; $e =  $cp[$i][1][1];
            if ($s <= $last) {
               $lp = &$passages[sizeof($passages)-1];
               if ($s != $last) {
                  print "[[Warning]] passage overlap ($s<=$last) - merging!\n";
                  fwrite($files[$pool],"  <!-- Warning: passage overlap ($s<=$last) - merging -->\n");
               }
               $error = true;
               $s = $lp[0];
               $e = $lp[1] = max($lp[1],$e);
            } else {
               $passages[] = array($s, $e);
            }
            print "  P[" . $s . ":" . $e . "]\n";
            $last = $e;
         }

         foreach($j[$pool] as $path => $exh) {
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
               print "[[Warning]] Specificity is null !?!\nfor $s:$e ($path) -> " ;
               foreach($passages as $seg) print "[$seg[0],$seg[1]]";
               print "\n";
               fwrite($files[$pool],"  <!-- Ignored assessment (null specificity): path: $path, exhaustivity: " . ($exh == -1 ? "?" : $exh) . "-->\n");
               continue;
            }
            $size = ($e - $s + 1);
            $spe = $rsize / $size;;
            if ($rsize > $size)
               die("Specificity is > 1 ($spe)!?!\nfor $s:$e ($path) -> " . print_r($passages,true) );

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
