<?
require_once("include/xrai.inc");

if ($_GET["error"]) {
   $r = mail("inex@poleia.lip6.fr","X-Rai bug report",stripcslashes($_GET["error"]),"reply-to: inex@poleia.lip6.fr");
   header("location: $PHP_SELF?errorm=" . rawurlencode(stripcslashes("$_GET[error]")) . "&success=" . $r . "&whattodo=". rawurlencode(stripcslashes($_GET["whattodo"])));
   exit;  
}

make_header("Bug report");

if (!$_GET["success"]) print "<div class='error'>The email could not be send. Please copy the following error message and send it to <a href=\"mailto:$xrai_admin_email\">$xrai_admin_email</a></div>";

print '<div style="margin-left: auto; margin-right: auto; margin-top: 0.3cm; padding: 5px; background: #ffeeee; border: solid 1px red">The following error message was sent:<div><code>' . nl2br(htmlspecialchars(stripcslashes($_GET["errorm"]))) . '</code></div></div>';

if (!empty($_GET["whattodo"])) print '<div style="margin: 5px; ">' . stripcslashes($_GET["whattodo"]) . '</div>';

make_footer();
?>