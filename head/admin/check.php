<?

include_once("../inex.inc");
include_once("../assessments.php");
include_once("common.php");

$checking_pool = true;
if (!($check_id > 0)) exit("Pool '$check_id' is not valid");
$id_pool=$check_id;

$qh = sql_query("SELECT DISTINCT f.name FROM files f LEFT JOIN $db_assessments a ON a.id_pool=$id_pool AND a.assessment<>'U' AND a.xid >= f.xid AND a.xid <= f.post WHERE f.name NOT LIKE '%/volume' AND a.xid is not null");

while ($row = sql_fetch_array($qh)) {
	print " Processing file $row[name] for pool $check_id";
	$doc_assessments = new Assessments($check_id,"$row[name]","/article[1]");
	$doc_assessments->inference();
	$doc_assessments->update_database(false);
	unset($doc_assessments);
	print " - Done\n";
}

?>
