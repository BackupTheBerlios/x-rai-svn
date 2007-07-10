<?php

/**************************
 * Gestion de formulaires *
 **************************/

$bgTableHeader = "#ccddcc";
$bgTableColor = "#d0d0d0";


// --- Affichage du formulaire (v1)
// ---------------------------

function form_start($title) { 
  global $bgTableHeader, $bgTableColor;
  print "\n\n<!-- Form -->\n<table cellpadding=\"3\" cellspacing=\"0\" border=\"0\">";
  print '<tr><td colspan="2"  bgcolor="' . $bgTableHeader . '"><b>' . htmlentities($title) . '</b></td><tr>';
}

function form_end() {
  print "</table><br /><br />";
}

function formA($msg) {
  global $bgTableColor;
  print "<tr bgcolor=\"$bgTableColor\"><td><b>" . nl2br(htmlentities($msg)) . "</b></td><td>";
}

function formZ() {
  print "</td></tr>\n";
}


function form_textinput($title,$no_topicame,$value,$size,$maxlength) {
  formA($title);
  print "<input name=\"$no_topicame\" type=\"text\" size=\"$size\" maxlength=\"$maxlength\" value=\"" . htmlentities($value) . "\" />";
  formZ();
}

function form_textarea($title,$no_topicame,$value,$cols,$rows) {
  formA($title);
  print "<textarea rows=\"$rows\" cols=\"$cols\"  name=\"$no_topicame\">" . htmlentities($value) . "</textarea>";
  formZ();
}
function form_passwordinput($title,$no_topicame,$value,$size,$maxlength) {
  formA($title);
  print "<input name=\"$no_topicame\" type=\"password\" size=\"$size\" maxlength=\"$maxlength\" value=\"" . htmlentities($value) . "\" />";
  formZ();
}

function form_submit($name) {
  formA("");
  print "<input  type=\"submit\" name=\"submit\" value=\"" . htmlentities($name) . "\" />";
  formZ();
}

function form_hidden($no_topicame,$value) {
  print "<input type=\"hidden\" name=\"$no_topicame\" value=\"" . htmlentities($value) . "\" />";
}

function form_export($no_topicame, $strip=true) {
  global $$no_topicame;
  print "<input type=\"hidden\" name=\"$no_topicame\" value=\"" . htmlentities(($strip ? stripslashes($$no_topicame) : $$no_topicame)) . "\" />";
}


function form_select_sql($title,$no_topicame,$defaut,$choices,$no_topicull=0,$select_options="") {
  formA($title);
  print "<select $select_options name=\"$no_topicame\">\n";
  if ($no_topicull) print "<option value=\"\">-</option>\n";
  while ($choice = mysql_fetch_row($choices)) 
    print "<option value=\"" . $choice[0] . "\""
      . ($choice[0] == $defaut ? " selected=\"selected\"" :  "")
      . ">" . htmlentities($choice[1]) . "</option>\n";
  print "</select>\n";
  formZ();
}

function form_select($title,$no_topicame,$defaut,$choices,$no_topicull=0) {
  formA($title);
  print "<select name=\"$no_topicame\">\n";
  if ($no_topicull) print "<option value=\"\">-</option>\n";
  for($i=0; $i < sizeof($choices); $i++)
    print "<option value=\"" . $choices[$i][0] . "\""
      . ($choices[$i][0] == $defaut ? " selected=\"selected\"" :  "")
      . ">" . htmlentities($choices[$i][1]) . "</option>\n";
  print "</select>\n";
  formZ();
}




// ---- Champ "date"
// -----------------


$monthTable[0] = "-";
$monthTable[1] = "janvier";
$monthTable[2] = "février";
$monthTable[3] = "mars";
$monthTable[4]  = "avril";
$monthTable[5]  = "mai";
$monthTable[6]  = "juin";
$monthTable[7]  = "juillet";
$monthTable[8]  = "août";
$monthTable[9]  = "septembre";
$monthTable[10] = "octobre";
$monthTable[11] = "novembre";
$monthTable[12] = "décembre";


function form_datefield($title,$no_topicame,$defaut="0000-00-00") {
  global $monthTable;
  formA($title);

  ereg("^([[:digit:]]+)-([[:digit:]]+)-([[:digit:]]+)",$defaut,$date);

  $jour = $date[3]; $mois = $date[2]; $annee = $date[1];

  settype($jour,"integer");  settype($mois,"integer");  settype($annee,"integer");
  if ($annee == 0) $annee=date("Y");


  print "\n<select name=\"$no_topicame" . "[0]\">";
  for($i=1;$i < 32; $i++)
    if ($jour==$i) print "<option selected value=\"$i\">$i</option>";
    else print "<option value=\"$i\">$i</option>";
  print "</select>\n";

  print "<select name=\"$no_topicame" . "[1]\">";
  for($i=1;$i < 13; $i++)
    if ($mois==$i) print "<option selected value=\"$i\">" . htmlentities($monthTable[$i]) . "</option>";
    else print "<option value=\"$i\">" . htmlentities($monthTable[$i]) . "</option>";
  print "</select>\n";


  print "<select name=\"$no_topicame" . "[2]\">";
  $cyear = strftime("%Y");

  for($i=1998;$i < $cyear+5; $i++)
    if (($annee==$i) || ($annee==0 && $cyear==$i)) print "<option selected value=\"$i\">$i</option>";
    else print "<option value=\"$i\">$i</option>";
  print "</select>\n";
  formZ();


}






// ---- URI et redirections
// ------------------------

function url_addvar($url,$x,$value) {
  if (!ereg("\?[^/]*",$url)) $url .= "?";
  if (!ereg("\?$",$url)) $url .= "&";
  return $url . rawurlencode($x) . "=" . rawurlencode($value);
}

function form_redirect($url,$message) {
  header("location: " . url_addvar($url,"message",$message));
  exit();
}



?>
