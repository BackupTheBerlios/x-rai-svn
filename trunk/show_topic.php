<?
/**
    show_topic.php
    Show the topic
    
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
require_once("include/xslt.inc");
  include_once("include/xrai.inc");
$id_topic=$_REQUEST["id_topic"];
print "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
?>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<title>INEX - View topic  n°<? print $id_topic; ?> view</title>
<link rel="stylesheet" href="style.css"/>
<body>
   <?



if ($id_topic > 0) {
   $xslfile = dirname(__FILE__) . "/xsl/topic.xsl";
   $xslt = get_xslt_processor();
   if (!$xslt) print "<div class='error'>No XSLT processor defined !</div>";
   $a = $xrai_db->getRow("SELECT definition FROM $db_topics WHERE id=?",array($id_topic),DB_FETCHMODE_ARRAY);
   if (!DB::isError($a)) {
      if ($xslt_mode) {
         $params = array("/_xml" => $a[0], "/_xsl" => file_get_contents("$xslfile"));
         print xslt_process($xslt,"arg:/_xml","arg:/_xsl", NULL, $params);
      } else {
         $xslt->importStyleSheet(DOMDocument::load($xslfile));
         print $xslt->transformToXML(DOMDocument::loadXML($a[0]));
      }
   } else {
         non_fatal_error("Could not retrieve topic n°$id_topic definition",$a->getUserInfo());
   }
  print "<div><a href='javascript:window.close()'>Close window</a></div>";

} else {
  print "No topic defined.<br/>";
}

?>
</body>
</html>