<?

function collectionStartDocument($collection,$file,$title) {
  print "<p style=\"color: #888; text-align: center; margin: 3px;\">Wikipedia link: <a href=\"http://en.wikipedia.org/wiki/" . rawurlencode($title) . "\">" . $title . "</a></p>";

}

function collectionStartElement($name, &$attrs) {
  global $base_url;
   if ($name == "image") {
      print "<html:img alt=\"[img]\" title=\"\" src=\"$base_url/img/xxx\">"; //<html:div style='display: none'>";
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