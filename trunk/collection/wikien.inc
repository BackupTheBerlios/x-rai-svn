<?

function collectionStartDocument($collection,$file,$title) {
?>
<script language="javascript">

</script>
<?
  print "<p style=\"color: #888; text-align: center; margin: 3px;\">Wikipedia link: <a href=\"http://en.wikipedia.org/wiki/" . rawurlencode($title) . "\">" . htmlspecialchars($title) . "</a></p>";
  global $xml_documents;
  if (!is_dir($xml_documents))
   print "<div class='warning'>Problems with the connection to the Wikipedia XML documents server.</div>\n";

}

function collectionStartElement($name, &$attrs) {
  global $base_url, $xrai_settings, $xrai_db;
   if ($name == "image") {
      $pname = preg_replace(array('#^../pictures/#','# #'),array('','_'),$attrs["xlink:href"]);
      $row = $xrai_db->getRow("SELECT * FROM wikienimages WHERE name=?", array($pname), DB_FETCHMODE_ASSOC);
      if (!DB::isError($row) && $row) {
         $filename = (intval($row["id"] / 100000) % 100) . "/" . (intval($row["id"] / 1000) % 100) . "/" . $row["id"];
         $path = $xrai_settings["wikien"]["mediadir"] . "/$filename";
         if (is_file($path) && $row["width"] == 0) {
            
         }
         print "<html:img alt=\"[$filename]\" title=\"" . htmlspecialchars($pname) . "\" src=\"$base_url/media/wikien/$filename\">"; 
      } else print "<html:img title=\"" . htmlspecialchars($pname) . " (not found)\" alt=\"[Not found: " . htmlspecialchars($pname) . "]\">";
   } 
}

function collectionPreStartElement($name, &$attrs) {
  if ($name == "collectionlink") {
   	$attrs["xlink:href"] =  preg_replace("#&file=\d+#","&file=".preg_replace("#.xml$#","",$attrs["xlink:href"]),$_SERVER["REQUEST_URI"]);
   } else if ($name == "cell") {
      if ($attrs["rowspan"]) $attrs["html:rowspan"] = $attrs["rowspan"];
      if ($attrs["colspan"]) $attrs["html:colspan"] = $attrs["colspan"];
   } 
}   
   
function collectionEndElement($name) {
   if ($name == "image") print "</html:img>";
}


?>