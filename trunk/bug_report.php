<?
/*
    bug_report.php
    Send a quick bug report for very predictible bugs (i.e. related to bad XML documents)
    
    Copyright (C) 2003-2007  Benjamin Piwowarski benjamin@bpiwowar.net

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Library General Public
    License as published by the Free Software Foundation; either
    version 2 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Library General Public License for more details.

    You should have received a copy of the GNU Library General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
*/
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