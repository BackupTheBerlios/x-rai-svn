<?


/* This script generates one assessment file by pool together with an HTML page with statistics
*/


$remote=true;
ignore_user_abort(false);
set_time_limit(0);
chdir("..");
require_once("include/xrai.inc");
require_once("include/assessments.inc");

if (sizeof($argv) != 2) { 
	print "download_pools <state>\n";
	exit(1);
}

$state = $argv[1];
$outdir = "$assessmentsdir/$state";
if (!is_dir($outdir)) {
  print "$outdir is not a directory\n";
  exit(1);
}




// DTD file

function write_dtd($subdir) {
global $outdir;
$dtd_file = fopen("$outdir/$subdir/assessments.dtd","w");
fwrite($dtd_file,'
  <!ELEMENT assessments (file*)>
  <!ELEMENT file (path*)>
  <!ELEMENT path EMPTY>
  
  <!ATTLIST assessments 
            pool CDATA #REQUIRED
            topic CDATA #REQUIRED>
  <!ATTLIST file file CDATA #REQUIRED>
  <!ATTLIST path
  	path	CDATA	#REQUIRED
	exhaustiveness   (0|1|2|3|U)	"U"
	specificity	(0|1|2|3|U)	"U"
   inpool (true|false) "false"
	inferred	(true|false) "false"
	inconsistant (true|false) "false"
  >
  ');
fclose($dtd_file);
}

// Statistics 
$stat_filename = "$outdir/statistics.html";
$stat_file=fopen("$stat_filename","w");
if (!$stat_file) { print "Can't open file '$stat_filename'\n"; exit(1); }

$all_assessments = array_merge(array('I'),$sorted_assessments);

fwrite($stat_file,"<table class='stats'><thead><tr><th>Pool name</th>");
foreach($all_assessments as $a) fwrite($stat_file,"<th>" . get_assessment_img($a,false,false,true,$a == 'I') . "</th>");
fwrite($stat_file,"<th>% Done</th></tr></thead>\n<tbody>\n");

// -*- get the file of a given element
function get_file($xid) {
    $qhf = sql_get_row("SELECT xid,post,name FROM files WHERE $xid >= xid AND $xid <= post AND type='xml'");
    return array($qhf["xid"],$qhf["post"],$qhf["name"]); 
}

// -*- Write assessment files

function write_stats($fh,$pool_name,&$stats) {
	global $all_assessments;
	fwrite($fh,"<tr><td style='span: 2pt; border-right: 1pt solid black'>$pool_name</td>");
	foreach($all_assessments as $a) 
		fwrite($fh,"<td style='span: 2pt' title='" . intval(1000 * ($stats[$a]/$stats["T"]))/10 . " %'>" . intval($stats[$a])  . " </td>");
	fwrite($fh,"<td style='border-left: 1pt solid black'>" . 
			intval(100 * (1. - (($stats["U"]+$stats["I"])/$stats["T"]))) . " %</td></tr>\n");
}
// print "SELECT * FROM pools,topics WHERE state='$state' AND topics.id = pools.id_topic order by id_pool\n";
$pools = sql_query("SELECT * FROM pools,topics WHERE state='$state' AND topics.id = pools.id_topic order by id_pool");
$nb_pools = sql_num_rows($pools);

// Loop on different pools

mkdir("$outdir/done"); // Directory with links to done pools
write_dtd("done");
mkdir("$outdir/in_progress"); // Directory with links to done pools
write_dtd("in_progress");

$no_pool = 0;
$xsl = '<?xml version="1.0" encoding="iso-8859-1"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output encoding="iso-8859-1" method="text"/>
<xsl:template match="/"><xsl:value-of select="/inex_topic/@query_type"/></xsl:template>
</xsl:stylesheet>
';

while ($pool = sql_fetch_array($pools)) {
   print "Downloading pool n°$pool[id_pool]\n";
   $no_pool++;
   $status_fh = @fopen("$assessmentsdir/waiting/$state.stats","w");
   @fwrite($status_fh,"$no_pool\n$nb_pools\n");
   @fclose($status_fh);
     
   $is_done = sql_query("SELECT * FROM assessments WHERE id_pool = $pool[id_pool] AND (assessment='U' OR inconsistant='Y') LIMIT 0,1");
  
  $params = array("/_xml" => $pool["definition"], "/_xsl" => $xsl); 
  $pool_type = xslt_process($xslt,"arg:/_xml","arg:/_xsl", NULL, $params);
  if (!$pool_type) $pool_type = "misc"; 
  $dname = "$outdir/" 
   . ( sql_num_rows($is_done) > 0 ? "in_progress" : "done") . "/$pool_type";
  if (!is_dir($dname)) mkdir($dname); 
  $dname .= "/topic-$pool[id_topic]"; 

  // We don't ask for filename yet as MySQL (4.0) is not very optimised for this type of query (range)    
  $qh = sql_query("SELECT a.xid xid, p.path path, inferred, inconsistant, assessment, in_pool FROM assessments a, paths p, map m WHERE a.id_pool = $pool[id_pool] and a.xid = m.xid and p.id = m.path  ORDER BY m.xid");

$current_file = "";
unset($stats);
if (!is_dir($dname)) mkdir($dname);

$assess_file = fopen("$dname/pool-$pool[id_pool].xml","w");
fwrite($assess_file,'<?xml version="1.0"?>
<!DOCTYPE assessments SYSTEM "../../assessments.dtd">
<assessments' . " pool='$pool[id_pool]' topic='$pool[id_topic]'>\n");
  


$file = array(-1,-1,""); // current pre post name
while ($row = sql_fetch_array($qh)) {
   // Retrieve the filename if needed
   if ($row["xid"] < $file[0] || $row["xid"] > $file[1]) {
      // update !
      $file = get_file($row["xid"]);
      $filename = $file[2];
//      print "Cache updated ($file[0],$row[xid],$file[1])\n" ;
} 
// else print "Cache OK ($file[0],$row[xid],$file[1])\n";
     
	if ($current_file != $filename) {
		if ($current_file) fwrite($assess_file,"\t\t</file>\n");
		$current_file = $filename;
		fwrite($assess_file,"\t\t<file file='$current_file'>\n");
	}
	fwrite($assess_file,"\t\t\t<path path='$row[path]'"
		. ($row["assessment"] != 'U' ? " exhaustiveness='" . $row[assessment][0] . "' specificity='" . $row[assessment][1] . "'" : "")
		. ($row["inferred"] == 'Y' ? " inferred='true'" : "")
		. ($row["inconsistant"] == 'Y'? " inconsistant='true'" : "")
      . ($row["in_pool"] == 'Y' ? " inpool='true'" : "")
		. "/>\n");
	$a = ($row["inconsistant"] == 'Y' ? 'I' : $row["assessment"]);
	$stats[$a]++;
	$stats["T"]++;
}
  if ($current_file) fwrite($assess_file,"\t\t</file>\n");
  fwrite($assess_file,"</assessments>");
  fclose($assess_file);
  write_stats($stat_file,$pool_name,$stats);
  sql_free($qh);
}
sql_free($pools);

fwrite($stat_file,"</tbody></table>\n");
fclose($stat_file);

?>
