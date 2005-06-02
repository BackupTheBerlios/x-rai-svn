<?
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
?>
<html><head>Update stats</head><body><script language="javascript">
var redo = true;
<?
      $dir=$_GET["dir"];
      $file  = "$dir/waiting/$_GET[state]";
      $lsf = "$dir/last-check" ; 
 if (@is_file($file)) {
   if (@is_file("$file.lock") && @is_file("$file.stats")) {
 $pstats = @file("$file.stats");
 $pratio = $pstats[1] > 0 ? intval($pstats[0] / $pstats[1]*100) : "?";  
 
// date("Y, M d \a\\t H:i",filemtime("$assessmentsdir/waiting/$view_state.lock"))
?>
      var pr ="<?=$pratio?>";
      parent.update_text("pratio_span",pr);
     if (pr > 0) parent.pratio_bar.style.width =  pr + "%";
     if (pr == "100") { alert("Assessments are ready to download"); redo = false; }
      if (parent.updating_div.style.visibility == "hidden") {
         parent.waiting_div.style.visibility = "hidden";
         parent.waiting_div.style.position = "absolute";
         parent.updating_div.style.visibility = "visible";
         parent.updating_div.style.position = "relative";   
         parent.update_text("proc_since","<?=@date("Y, M d \a\\t  H:i",@filemtime("$file.lock"))?>");
      }
<?
} else { // No lock file = not processing, we only check visibility of waiting div
?>
   if (parent.waiting_div.style.visibility == "hidden") {
   parent.waiting_div.style.visibility = "visible";
   parent.waiting_div.style.position = "relative";   
   parent.update_text("waiting_since","<?=date("Y, M d \a\\t  H:i",filemtime($file))?>");
   parent.update_text("last_check","<?=date("Y, M d \a\\t  H:i",filemtime($lsf))?>");
   }
<?
  }
 ?>
      function updateStats() {  document.location.reload(); }
      if (redo) setTimeout("updateStats()", 10000);
//      alert("Updating in 5 secs"); 
<?
} // Something is happening
?>
</script>
      THIS IS AN INTERNAL UPDATE FRAME!
</body>
</html>