<?

function ieee_startElement($name, $attrs) {
   if ($name == "art")
   print "<html:img src=\"$media_url/$collection/$directory/" .  strtolower(preg_replace("/\.gif$/",".png",$attrs["file"])) . "\"/>";
}

?>