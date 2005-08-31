<?
/*
  Call the XSL preprocessor to compute caches for XML files
*/
$stderr = fopen('php://stderr', 'w');

chdir("..");
$remote = true;
$precompute = true;
ignore_user_abort(true);
set_time_limit(0);
include_once("include/xrai.inc");
include_once("include/assessments.inc");
include_once("admin/common.php");

function check() {
  global $argc, $stderr;
  if ($i+1 >= $argc) { fwrite($stderr,"argument outside command line\n"); exit(1); }
}

$doit=0;
for($i = 1; $i < $argc; $i++) {
 switch($argv[$i]) {
   case "-login": check($i); $new_login = $argv[$i+1]; $i++; break;
   case "-prefix": check($i); $collection_prefix = $argv[$i+1]; $i++; break;
   case "-name": check($i); $new_pool_name = $argv[$i+1]; $i++; break;
   case "-state": check($i); $state = $argv[$i+1]; $i++; break;
   case "-file": check($i); $filename= $argv[$i+1]; $i++; break;
   case "-id": check($i); $id_topic = $argv[$i+1]; $i++; break;   
   case "-doit": $doit = 1; break;
   default: fwrite($stderr, "Unknown option: $argv[$i]\n"); exit(1);
 }
}
 
if (empty($new_login)) { print "-login must be given\n"; exit(1); }
if (empty($filename) || !file_exists($filename)) { print "-file is not valid\n"; exit(1); }
if (empty($new_pool_name)) $new_pool_name = basename($filename);
if (empty($id_topic) || !($id_topic > 0)) { fwrite($stderr,"-id not defined\n"); exit(1); }
if ($state != "official" && $state != "demo") { print "-state is neither 'official' nor 'demo'\n"; exit(1); }

 function startElement($parser, $tagname, $attrs) {
 	global $db_assessments, $collection_prefix, $stderr;
 	global $new_id_pool,  $currentfile, $doit;
	if ($tagname == "file") $currentfile = $attrs["file"];

	if ($tagname == "path") {
		if ($currentfile) {
		$xid = path2id($collection_prefix . $currentfile,$attrs["path"]);
		if (!$xid) {
			fwrite($stderr,"Can't convert $currentfile#$attrs[path]\n");
		} else {
 			if ($doit) sql_query("INSERT INTO $db_assessments (id_pool,in_pool,inferred,xid,enabled) VALUES ($new_id_pool, 'Y','N',$xid,'Y')","</div>");
//        $attrs[file]#$attrs[path]\n";
		}
		} else {
			fwrite($stderr,"No current file for path $attrs[path]");
		}
	}
}

function endElement($parser, $tagname) {
	if ($tagname == "file") $currentfile = false;
}


 if ($doit) {
    sql_query("INSERT INTO $db_pools (id_topic, login, name, state) VALUES('$id_topic','$new_login', '$new_pool_name', '$state')","</div>");
    $new_id_pool = mysql_insert_id(); 
  } else fwrite($stderr,"SIMULATION MODE\n");

$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, FALSE);

$fsize = filesize($filename);
$cratio = 0;
$bytes_read = 0;

$N=25;
fwrite($stderr,str_repeat("=",$N)."\n");
$fh = fopen($filename,"r");
while ($data = fread($fh, 4096)) {
	$bytes_read += strlen($data);
	$ratio = intval($N * $bytes_read / $fsize);
	if ($ratio > $cratio) {
		fwrite($stderr,str_repeat(".",$ratio-$cratio));
		$cratio = $ratio;
	}

    if (!xml_parse($xml_parser, $data, feof($fh))) {
        die(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser)));
    }
}
	fwrite($stderr,"Done.\n");

?>
