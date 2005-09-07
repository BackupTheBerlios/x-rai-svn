<?
$xslt_mode = function_exists("xslt_process");
function &get_xslt_processor() {
   global $xslt_mode ;
   if ($xslt_mode) {
      $xslt = xslt_create();
      xslt_set_encoding($xslt,"UTF-8");
      return $xslt;
   }
   if (class_exists("XSLTProcessor")) return new XSLTProcessor();
}
?>