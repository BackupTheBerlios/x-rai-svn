<?
  include_once("include/xrai.inc");
$id_topic=$_REQUEST["id_topic"];
print "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
?>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<title>INEX - View topic  n<? print $id_topic; ?> view</title>
<link rel="stylesheet" href="style.css"/>
<body>
   <?


if ($id_topic > 0) {
//   print "<h1>Topic n$id_topic</h1>";
   $xslfile = dirname(__FILE__) . "/xsl/topic.xsl";
//    xslt_set_base($xslt,"file://" + dirname(__FILE__) . "/dtds");

   if (!$xslt) print "<div class='error'>No XSLT processor defined !</div>";
   $a = $xrai_db->getRow("SELECT definition FROM $db_topics WHERE id=?",array($id_topic),DB_FETCHMODE_ARRAY);
   if (!DB::isError($a)) {
      $params = array("/_xml" => $a[0], "/_xsl" => file_get_contents("$xslfile"));
      print xslt_process($xslt,"arg:/_xml","arg:/_xsl", NULL, $params);
   } else {
         non_fatal_error("Could not retrieve topic n°$id_topic definition",$a->getUserInfo());
   }
  print "<div><a href='javascript:window.close()'>Close window</a></div>";

} else {
  print "No topic defined.<br/>";
}

?>
</body>
</html>