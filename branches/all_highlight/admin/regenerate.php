<html><head><title>Regenerate!</title></head><body>
<script language="javascript">
<?
chdir("..");
include_once("include/xrai.inc");
$state = $_REQUEST["state"];

if (is_file("$assessmentsdir/waiting/$state.lock")) {
  window.alert("The assessments file is now being (re)generated.");
} else {
  if (@touch("$assessmentsdir/waiting/$state")) 
    print 'window.alert("The assessments file will be (re)generated. Please wait...");';
  else 
    print 'window.alert("Impossible to ask for file generation!");';
}
?>
// update the main view now!
parent.updateStats(); 
</script>
</body>
</html>