<?

function collectionStartElement($name, $attrs) {
   global $media_url, $collection, $directory;
   if ($name == "art")
      print "<html:img alt=\"[img]\" style=\"width: $attrs[tw]; height: $attrs[th]\" src=\"$media_url/$collection/$directory/" .  strtolower(preg_replace("/\.gif$/",".png",$attrs["file"])) . "\"/>";
}

?>