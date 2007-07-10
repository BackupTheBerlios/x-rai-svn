<?

function getPercentage($x,$y) {
   return $y > 0 ? intval(10000*($x / $y))/100 : 0;
}


/** Draw a bar which length
*/
function drawABar($inpool, $n, $te, $total) {
   if ($n == 0) return;
   $c = $inpool ? "#aaf" : "#00a";
?>
<span title="<?= ($inpool? "[In pool] " : "") . "$n (out of $total) "?> document(s) to assess in this view" style='display: inline; padding-left: <?=floor(150*(1-exp(-0.01*$n)))?>px; margin-right: 5px; background: <?=$c?>; border: 1px solid #000;'></span>
<?
}

// Assessment status for collection/pool
function printStatus(&$t, &$ts) {
   global $base_url;
   drawABar(true,$t[1],$t[1]+$t[2], $ts[1]);
   drawABar(false,$t[-1],$t[-1]+$t[-2],  $ts[-1]);
}


?>