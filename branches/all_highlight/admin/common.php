<?

if (!$is_root) {
	header("Location: $base_url");
}

$progressbar_id = 0;

class ProgressBar {

	var $id;
	var $min;
	var $max;
	var $current;

	function ProgressBar($min, $max)
	{
		global $progressbar_id;
		$progressbar_id++;
		$this->id = "progress_$progressbar_id";
		$this->min = $min;
		$this->max = $max;
		$this->current = $min;
		?>
		<div style='background: red; width: 90%; padding: 0; margin: 5%;'>
		<div id='<?=$this->id?>' style='width: 0%; background: blue'>&nbsp;</div>
		</div>
		<script language="javascript">
		var <?=$this->id?> = document.getElementById("<?=$this->id?>");
		</script>
		<?
	}

	function update($x) {
		$ratio = intval(100 * (($x-$this->min) / ($this->max - $this->min) ));
		if ($ratio > $this->current) {
			$this->current = $ratio;
			print "<script language='javascript'>\n$this->id.style.width =  '$ratio%';\n</script>\n";
			flush();
		}
	}

	function end() {
		?><script language="javascript"><?=$this->id?>.style.width =  "100%";</script><?
	}
}

function get_select_status($name,$default = "") {
	print "<select name='$name'>";
	foreach(array("official","demo") as $state) {
		print "<option id='${name}_${state}' value='$state'>" . ($state == $default ? " selected='yes'" : "") . "$state</option>";
	}
	print "</select>\n";
}

?>
