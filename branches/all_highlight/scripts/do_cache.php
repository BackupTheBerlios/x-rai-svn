<?
/*
  Call the XSL preprocessor to compute caches for XML files
*/

$force_update=0;
chdir("..");
$remote = true;
$precompute = true;
ignore_user_abort(true);
set_time_limit(0);
require_once("include/xrai.inc");
if ($argv[1] == "-force") {
  print "Force the update.\n";
  $force_update=1;
}
	
$list = sql_query("SELECT * FROM $db_files ORDER BY name");
while ($file_row = sql_fetch_array($list)) {

  if ($file_row["type"] == "xrai") continue;  
  print "Processing $file_row[name].$file_row[type] with $file_row[xsl].xsl:  ";
  $xslfilename = "xsl/$file_row[xsl].xsl";
  $file = $file_row["name"];
  $did = $file_row["xid"];
  $postid = $file_row["post"];
  $xmlfilename = "$xml_documents/$file.xml";
  include("include/process_article.inc");
//   if (!shell_exec("xmllint $phpfilename > /dev/null 2>&1")) print "!!! Problem with $file_row[name] ($phpfilename)\n";
}

?>
