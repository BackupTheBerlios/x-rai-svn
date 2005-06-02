<?
function startelement($parser, $name, $data) {
	global $parse_error, $tfh, $current_tags, $current_types, $sqlinfo, $current_xid, $in_xrai_maths,$xrai_transform_buffer;
	
	// print_tag("div","<xsl:value-of select="name(.)"/>","<xsl:apply-templates mode="localize" select="."/>"<!--,'<xsl:apply-templates mode="localize" select="@*"/>'//-->);

	switch($name) {
   case "showart": {
/*      show_art('$data[file]','$data[width]','$data[height]');");*/
      fwrite($tfh," <? show_art('$data[file]','$data[width]','$data[height]'); ?> ");   
     break;
   }
   
   case "xmltag": {
     $row = @mysql_fetch_row($sqlinfo);
     if (!$row || $row[0] != $current_xid) $parse_error = true;// { $parse_error = "$row, $row[0], $current_xid"; }
     $current_xid++;
     fwrite($tfh, "<? print_tag('$data[type]','$data[name]','$data[path]',$data[count],'$row[1]'); ?>"); 
// 		print "<div style='margin-left: " . (sizeof($current_types)*5) ."px'>" . $data["type"] . "</div>";
		$current_types[] = $data["type"];
		$current_tags[] =  $data["name"];
	  break;
   
 }
   case "endtag": {
     fwrite($tfh,"<?end_tag('$data[type]','$data[name]','$data[path]');?>");   
     break;
   }
   case "xrai-maths": 
      $xrai_transform_buffer = "";
      $in_xrai_maths = true;
      break;

   default:{
 		fwrite($tfh,"<$name");
      foreach($data as $k => $v) fwrite($tfh," $k=\"" . preg_replace(array('/&/','/</','/>/',"/\"/"),array('&amp;','&lt;','&gt;','&quote;'),$v) . "\"");
		fwrite($tfh,">\n");
}
	}
}

               
function endelement($parser, $name) {
   global $tfh, $current_tags, $current_types, $in_xrai_maths, $xrai_transform_buffer, $no_mathml;
	switch($name) {
   case "showart": 
   case "endtag":
      break;
   case "xrai-maths":  
      if ($xrai_transform_buffer) {
         if ($no_mathml) {
            fwrite($tfh,"<code title='This latex code was not processed (MathML is disabled)'>" . htmlspecialchars($xrai_transform_buffer) . "</code>");
         } else
         if (preg_match('/^\s*\$.*\$\s*$/',$xrai_transform_buffer)) {
            $descriptorspec = array(
              0 => array("pipe", "r"),  // stdin est un pipe où le processus va lire
              1 => array("pipe", "w"),  // stdout est un pipe où le processus va écrire
//               2 => array("file", "/dev/null", "a"), // stderr est un fichier
            );
            $process = @proc_open("./bin/ttm -r -L", $descriptorspec, $pipes);
            if (is_resource($process)) {
//                fwrite($tfh, "<!-- maths buffer is ::: " . htmlspecialchars($xrai_transform_buffer) . " ::: -->\n");
//             $xrai_transform_buffer = preg_replace("/'/",'\\\'',$xrai_transform_buffer);
               fwrite($pipes[0], $xrai_transform_buffer);
               fclose($pipes[0]);
               $s = "";
               while(!feof($pipes[1])) $s .= fgets($pipes[1],1024);
               fclose($pipes[1]);
               $xml_parser = xml_parser_create();
               if (xml_parse($xml_parser,"<none>" . preg_replace('/&\w+;/','',$s) . "</none>",true)) {
                  fwrite($tfh,$s);
               } else {
                  $em = htmlspecialchars(xml_error_string(xml_get_error_code($xml_parser)) . ", " . xml_get_current_line_number($xml_parser));
                  fwrite($tfh,"<code title='This latex code was not processed (invalid XML code) : $em'>" . htmlspecialchars($xrai_transform_buffer) . "</code>");
               }
               xml_parser_free($xml_parser);
               $return_value = proc_close($process);
            } else {
               fwrite($tfh,"<code title='This latex code was not processed (error while running ttm)'>" . htmlspecialchars($xrai_transform_buffer) . "</code>");
            }
         } else {
            fwrite($tfh,"<code title='This latex code was not processed (doesn&apos;t seem valid tex code)'>" . htmlspecialchars($xrai_transform_buffer) . "</code>");
         }
       }
      $in_xrai_maths = false;
      break;
   case "xmltag":
// 		print "Here: " . $current_tags[sizeof($current_tags)-1] . "<br/>";
// 		print "<div style='margin-left: " . (sizeof($current_types)*5) ."px'>/" . array_pop($current_types) . "</div>";
		fwrite($tfh, "</" . $current_types[sizeof($current_types)-1] . ">\n");
      fwrite($tfh,"<?end_tag_up();?>");   
      array_pop($current_tags);
		array_pop($current_types);
    break; 
    
   default:
	  fwrite($tfh,"</$name>");
 }
}


function characters($parser, $data) {
   global $tfh, $current_tags, $xrai_transform_buffer, $in_xrai_maths;
   if ($in_xrai_maths) {
      $xrai_transform_buffer .= $data;
      return;
   }
   
   fwrite($tfh,preg_replace(array('/&/','/</','/>/'),array('&amp;','&lt;','&gt;'),$data));

}

?>