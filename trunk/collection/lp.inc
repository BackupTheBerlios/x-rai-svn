<!--


-->
<?
// <image filename="/images/plug_types/elec_4.gif">


function collectionEndElement($name) {
}

function collectionStartElement($name, $attrs) {
   global $media_url, $collection, $base_url,$directory, $tagstack;
   if ($name == "image") {
            print "<html:img alt=\"[img]\" src=\"$base_url/media/lp" .  $attrs["filename"] . "\"/>";
   }
   else if ($name == "map") {
            print "<html:img alt=\"[map]\" src=\"$base_url/media/lp" .  $attrs["filename"] . "\"/>";
   }
}

?>