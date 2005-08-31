<?

include_once("../inex.inc");
include_once("common.php");

make_header("Test");
$qh = sql_query("SELECT f.name FROM files f LEFT JOIN assessments a ON a.xid >= f.xid AND a.xid <= f.post AND a.id_pool = 171 WHERE a.xid IS NOT NULL AND NOT ( f.name LIKE '%volume')");
$nb = sql_num_rows($qh);
print "<div class='message'>$n files to check</div>";

$bar = new ProgressBar(0,$nb);
$n = 0;
while ($row = sql_fetch_array($qh)) {
	$bar->update(++$n)
	$xid = path2id("dt/2001/d6070","/article[1]/bdy[1]/index[2]/index-entry[18]/index-entry[8]/h[1]");
	$bar->update($i);
	print "$xid[0], $xid[1]";
}

make_footer();

?>
