<?

chdir("..");
include_once("include/xrai.inc");
include_once("include/assessments.inc");
include_once("admin/common.php");

// Abort with user
set_time_limit(60 * 60); // 1 hour
ignore_user_abort(true);

$pool_file = $_FILES["pool_file"];
$new_login = $_POST["new_login"];
$doit = $_POST["doit"];
$id_topic = $_POST["id_topic"];
$state = $_POST["state"];
$collection_prefix = $_POST["collection_prefix"];
if (!empty($collection_prefix) && !preg_match('#/$#',$collection_prefix)) $collection_prefix .= "/";

if (!$pool_file ) {
make_header("Add a pool");
?>
<style type="text/css">
	form div { margin-top: 2pt; padding: 0.1cm; border-top: 1pt solid black; border-bottom: 1pt solid black; background: #eeeeff}
 	form div span { color: #000099; font-weight: bold; }
</style>

<h1>Pool information</h1>
<form enctype="multipart/form-data" action="add_pool.php" method="post">
<div><span>Check to add in database</span>     <input type="checkbox" name="doit"/></div>
<div><span>Topic id</span>     <select type="text" name="id_topic">
<?
$topics = mysql_query("SELECT id from $db_topics order by id");
while ($t = mysql_fetch_array($topics)) print "<option value='$t[0]'>$t[0]</option>\n";
?>
</select></div>
<div><span>Pool login</span>  <input type="text" name="new_login"/></div>
<div><span title="Add a collection prefix">Collection prefix</span>  <input type="text" name="collection_prefix"/></div>
<div><span>Pool name</span>  <input type="text" name="new_pool_name"/></div>
<div><span>Pool state</span>  <?=get_select_status("state");?></div>
<div><span>Pool file</span>   <input type="file" name="pool_file"/></div>
<div><input type="submit" value="add"/></div>
</form>
<?
make_footer();
exit;
}

//if ($new_id_pool <= 0 && $new_id_pool >  32767) fatal_error("Pool id must be between 1 and 32767");

 function startElement($parser, $tagname, $attrs) {
 	global $db_assessments, $collection_prefix;
 	global $new_id_pool,  $currentfile, $doit;
	if ($tagname == "file") $currentfile = $attrs["file"];

	if ($tagname == "path") {
		if ($currentfile) {
		$xid = path2id($collection_prefix . $currentfile,$attrs["path"]);
		if (!$xid) {
			print "<div style='color:red'>Can't convert $currentfile#$attrs[path]</div>";
		} else {
 			if ($doit) sql_query("INSERT INTO $db_assessments (id_pool,in_pool,inferred,xid) VALUES ($new_id_pool, 'Y','N',$xid)","</div>");
//        $attrs[file]#$attrs[path]\n";
		}
		} else {
			print "<div style='color:red'>No current file for path $attrs[path]</div>";
		}
	}
}

function endElement($parser, $tagname) {
	if ($tagname == "file") $currentfile = false;
}

make_header("Adding a new pool");


$fsize = filesize($_FILES["pool_file"]['tmp_name']);
$cratio = 0;
$bytes_read = 0;

print "<div class='message'>Processing file " . $_FILES[pool_file][name] . "</div><div>";
if (!$doit) print "<div class='message'>Simulation (not inserting in database)</div>\n";
?>
<div style='background: red; width: 90%; padding: 0; margin: 5%;'>
<div id='progress' style='width: 0%; background: blue'>&nbsp;</div>
</div>
<script language="javascript">
var progress = document.getElementById("progress");
</script>
<?

if ($doit) {
    sql_query("INSERT INTO $db_pools (id_topic, login, name, state) VALUES('$id_topic','$new_login', '$new_pool_name', '$state')","</div>");
    $new_id_pool = mysql_insert_id(); 
  }

$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, FALSE);

$fh = fopen($_FILES['pool_file']['tmp_name'],"r");
while ($data = fread($fh, 4096)) {
	$bytes_read += strlen($data);
	$ratio = intval(100 * $bytes_read / $fsize);
	if ($ratio > $cratio) {
		$cratio = $ratio;
		print "<script language='javascript'>\nprogress.style.width =  '$ratio%';\n</script>\n";
		flush();
	}

    if (!xml_parse($xml_parser, $data, feof($fh))) {
        die(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser)));
    }
}
?><script language="javascript">progress.style.width =  "100%";</script><?
fclose($fh);
print "</div>";
print "<div class='message'>Done</div>";
make_footer();

?>
