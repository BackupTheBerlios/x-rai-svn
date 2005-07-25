<?
header("Pragma: no-cache");
$id_topic=$_REQUEST["id_topic"];
?>
<html><head><title>INEX - View topic  n°<? print $id_topic; ?> view</title>
<link rel="stylesheet" href="style.css"/>
<?


if ($id_topic > 0) {
  include_once("include/xrai.inc");
//   print "<h1>Topic n°$id_topic</h1>";
  $xslfile = dirname(__FILE__) . "/xsl/topic.xsl";
   if (!$xslt) print "<div class='error'>No XSLT processor defined !</div>";
	$r = mysql_query("SELECT definition from $db_topics where id='$id_topic' and 1");
	if ($r && mysql_num_rows($r) == 1) {
	$a = mysql_fetch_array($r);
	$params = array("/_xml" => $a[0]);
	print xslt_process($xslt,"arg:/_xml","$xslfile", NULL, $params);
//   print $phpfile;
//   include($phpfile);
} else { print "<div class='error'>Could not retrieve the topic $id_topic!</div>";}
  print "<div><a href='javascript:window.close()'>Close window</a></div>";

} else {
  print "No topic defined.<br/>";
}

?>
