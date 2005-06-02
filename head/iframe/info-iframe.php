<?
//   include("include/xrai.inc");
  $file = $_REQUEST["file"];
if ($file != "changelog" && $file != "todo") exit();
?>
<html><head>
<style>
div.log {
	margin-top: 5pt;
	margin-bottom: 5pt;
}

div.log div {
	background: #ffffff;
	margin: 0;
	margin-bottom: 1px;
	padding: 3pt;
	border-bottom: #888899 1px solid;
}

div.log div span.when {
	width: 200pt;
	color: #444444;
}

div.log div.wishlist span.what {
	border-left: 2pt solid #FF0000;
	padding-left: 3pt;
}

div.log div.bug span.what {
	border-left: 2pt solid #FF0000;
	padding-left: 3pt;
}

div.log div.fixed span.what {
	border-left: 2pt solid #00FF00;
	padding-left: 3pt;
}


div.log div span.what {
	border-left: 2pt solid #0000FF;
	padding-left: 3pt;
}
</style>
</head><body>


<div class="log">
<?
  $last = 0;
	$fh = @fopen("../misc/$file","r");
	while (($when = @fgets($fh, 4096)) && ($infotype = @fgets($fh,4096)) && ($what = @fgets($fh,4096))) {
   $now = preg_replace('/-[^-]*$/','',$when);
   if ($now != $last) {  if ($last) print "<div style='padding-top:0.5cm; background: #eeeeee;'> </div>\n";$last = $now;  }
	?>
	<div class="<?=$infotype?>" title="<?=$infotype?>">
		<span class="when"><?=$when?></span>
		<span class="what"><?=$what?></span>
	</div>
	<?
	}
	@fclose($fh);
?>
</div>
</body></html>