<?php

/*
    download_pools.php
    Shows the article XML structure and allow navigation
    Not sure it works now with all the changes...    

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
