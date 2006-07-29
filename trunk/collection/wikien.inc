<?

function collectionStartDocument($collection,$file,$title) {
  print "<p style=\"color: #888; text-align: center; margin: 3px;\">Wikipedia link: <a href=\"http://en.wikipedia.org/wiki/" . rawurlencode($title) . "\">" . $title . "</a></p>";
  global $xml_documents;
  if (!is_dir($xml_documents))
   print "<div class='warning'>Problems with the connection to the Wikipedia XML documents server.</div>\n";

}

function collectionStartElement($name, &$attrs) {
  global $base_url, $xrai_db;
   if ($name == "image") {
      $pname = preg_replace(array('#^../pictures/#','#_#'),array('',' '),$attrs["xlink:href"]);
      $row = $xrai_db->getRow("SELECT * FROM wikienimages WHERE name=?", array($pname), DB_FETCHMODE_ASSOC);
      if (!DB::isError($row) && $row) {
         $filename = (intval($row["id"] / 100000) % 100) . "/" . (intval($row["id"] / 1000) % 100) . "/" . $row["id"];
         print "<html:img alt=\"[img]\" title=\"$pname\" src=\"$base_url/media/wikien/$filename\">"; 
      } else print "<html:img title=\"$pname (not found)\" alt=\"[image]\">";
   } 
}

function collectionPreStartElement($name, &$attrs) {
  if ($name == "collectionlink") {
   	$attrs["xlink:href"] =  htmlspecialchars(preg_replace("#&file=\d+#","&file=".preg_replace("#.xml$#","",$attrs["xlink:href"]),$_SERVER["REQUEST_URI"]));
   }
}   
   
function collectionEndElement($name) {
   if ($name == "image") print "</html:img>";
}


?>