<?php

/*

	Show an article
	B. Piwowarski, 2003

*/

chdir("..");
require_once("include/xrai.inc");
set_time_limit(360);


$file = $_REQUEST["file"];
$phpfilename = "$xml_cache/$file-treeview.php";
$xmlfilename = "$xml_documents/$file.xml";
$xslfilename = "xsl/treeview.xsl";

// $do_debug = true;
$tmpfile = "";
if ($dodebug || !hasCache($xmlfilename,$xslfilename,$phpfilename) || filesize($phpfilename) == 0) {
  @unlink($phpfilename);
  //print nl2br(htmlentities("$xmlcontent"));

  if (!file_exists($xmlfilename)) fatal_error("File $xmlfilename does not exist");

  $tmpfilename = "$phpfilename.tmp";
  $xslp = array("baseurl" => "$base_url/");
  if (!@xslt_process($xslt,"$xmlfilename","$xslfilename","$tmpfilename",$params,$xslp)) {
      @unlink("$phpfilename.tmp");
      fatal_error("XSLT error (1): " . xslt_error($xslt));
  }
  
  $phpfilename_cache = $phpfilename;
  $phpfilename = $tmpfilename;
}
if (! is_file("$phpfilename")) fatal_error("<div>Can't find processed XML file ($phpfilename). Please try to reload page or <a href='$base_url/informations.php'>report a bug</a>.</div>");

// set_error_handler("include_error_handler")
readfile($phpfilename);
if ($phpfilename_cache) @rename("$tmpfile",$phpfilename_cache);


?>
