<?

// This file is included in order to process an XML file
// The following variables *must* be defined:
//   - $xmlfilename
//   - $xslfilename
//   - $postid (document last element ID)
//   - $did (document ID)
//   - $precompute (true => do not output any file)
//   - $force_update (true => force the file update)


require_once("include/xrai.inc");
// Variables needed:

require_once("include/process_article_functions.inc");

$directory = dirname($file);
xslt_setopt($xslt,/*XSLT_OPT_SILENT|*/XSLT_SABOPT_PARSE_PUBLIC_ENTITIES);


$no_cache = false;
if (!is_dir("$xml_cache/$directory")) {
   if ($remote) fatal_error("No cache directory ($xml_cache/$directory)</div>");
  $phpfilename = tempnam("/tmp","xrai.php");
  $no_cache = true;
} else $phpfilename = "$xml_cache/$file.php";


// $do_debug = true;
$tmpfile = "";
if ($do_debug || $force_update  || !hasCache($xmlfilename,$xslfilename,$phpfilename) || filesize($phpfilename) == 0) {
	
//    print "$phpfilename";
  if ($no_cache) print "<div class=\"warning\">No cache directory</div>\n";
  if (!$remote) print "<div style='color: #888888;'>Processing (and caching) file with stylesheet</div>";
  @unlink($phpfilename);
  flush();
  //print nl2br(htmlentities("$xmlcontent"));
  
//   if (!file_exists($xmlfilename)) fatal_error("File $xmlfilename does not exist</div>");

  $tmpfile = "$phpfilename.tmp";
  $tfh = fopen("$phpfilename.tmp","w");
  xslt_set_sax_handlers($xslt,array("element" => array("startelement","endelement"), "character" => "characters"));
  // Retrieve info
//   $sqlinfo = mysql_query("SELECT xid,children_count FROM {$collection}_map WHERE post<=$postid AND xid>=$did ORDER BY xid");
  $current_xid = $did;
  $parse_error = false;
//   if (!$sqlinfo) fatal_error("Database error: " . mysql_error() . "</div>");
//    print "$xmlfilename and $xslfilename -&gt; $phpfilename.tmp<br/>";
  if (!@xslt_process($xslt,"$xmlfilename","$xslfilename")) {
      @unlink("$phpfilename.tmp");
      fatal_error("XSLT error (1): " . xslt_error($xslt) . "</div>");
  }
  fclose($tfh);
  @mysql_free_result($sqlinfo);
  
  //  chdir($cwd);
//   exec("bin/ttm -r < $tmpfile > $tmpfile.2");
  $phpfilename_cache = $phpfilename;
  $phpfilename = $tmpfile;

 if ($remote) print "Done.\n";
} else if ($remote) print "Already done.\n";

if (! is_file("$phpfilename")) fatal_error("Can't find processed XML file ($phpfilename). Please try to reload page or <a href='$base_url/informations.php'>report a bug</a>.</div>");
if ($parse_error) fatal_error("Parse error: $parse_error</div>");

// set_error_handler("include_error_handler")
$current_xid = $did; // Resets the current xid

if (!$precompute) @include($phpfilename);

if ($no_cache) @unlink($phpfilename);
else if ($phpfilename_cache) @rename("$tmpfile",$phpfilename_cache);
   


if (!$remote) print "</div>\n";
