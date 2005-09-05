<?

function collectionStartElement($name, $attrs) {
   global $media_url, $collection, $directory;
   if ($name == "art")
      print "<html:img src=\"$media_url/$collection/$directory/" .  strtolower(preg_replace("/\.gif$/",".png",$attrs["file"])) . "\"/>";
}

?>