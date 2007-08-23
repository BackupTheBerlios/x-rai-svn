<?php
// kate: indent-mode cstyle;
/**
    check_xml.php
    Check if an XML document is valid for X-Rai, i.e. that no element does 
    contain mixed content.

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
    
$xml_parser = xml_parser_create("UTF-8");
// xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "cdata");
xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,false);

if (sizeof($argv) != 2) {
   print "synopsis: $argv[0] XMLFILE\n";
   exit(2);
}
if (!is_file($argv[1])) {
   print "$argv[1] is not a file\n";
   exit(2);
}



$stack = Array();
$fp = fopen($argv[1],"r");

while ($data = fread($fp, 4096)) {
   $count += strlen($data);
   if (!xml_parse($xml_parser, $data, feof($fp))) {
       die(sprintf("XML error: %s at line %d</div>",
                   xml_error_string(xml_get_error_code($xml_parser)),
                   xml_get_current_line_number($xml_parser)));
   }
}
     
function update_stack(&$stack, $n) {
   if (sizeof($stack) == 0) return;
   $x = $stack[sizeof($stack)-1] |= $n;
   if ($x == 3) {
      exit(-1);
   }
}

function startElement($parser, $name, $attrs) {
   update_stack($GLOBALS["stack"], 2);
   array_push($GLOBALS["stack"], 0);
}

function endElement($parser, $name) {
   array_pop($GLOBALS["stack"]);
}

function cdata($parser, $data) {
  if (!preg_match('/^\s+$/',$data)) update_stack($GLOBALS["stack"], 1);
}

?>