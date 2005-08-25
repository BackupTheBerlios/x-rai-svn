<?
header("Pragma: no-cache");

// Report all PHP errors (bitwise 63 may be used in PHP 3)
// error_reporting (E_ALL);

require_once 'DB.php';

// Report all errors except E_NOTICE
error_reporting (E_ALL ^ E_NOTICE);

// Initializations
// ---------------

$xrains = "xrai:base";

if (!headers_sent()) header("Pragma: no-cache");
// include_once("assessments.php");

if (function_exists("xslt_create")) $xslt=xslt_create();
$header_done = false;
$inex_user = $_SERVER["REMOTE_USER"];
$id_pool = $_REQUEST["id_pool"];
$directory = $_REQUEST["directory"];
$file = $_REQUEST["file"];



$sorted_assessments = array("0","-1","1","2");

$relevances = array(
   "0" => array("a0","unknown"),
   "-1" => array("a-1","too small"),
    1 => array("a1","partially exhaustive"),
    2 => array("a2","highly exhaustive")
);



$REVIEW = array(
      "id" => 0,
      "url" => 1,
      "title" => 2,
      "years" => 3
   );


function find_byid($id) {
   global $inex_documents;
   for($i=0; $i < sizeof($inex_documents); $i++)
      if ($inex_documents[$i][0] == "$id") return $inex_documents[$i];
   return false;
}



// ---*--- hasCache($src1,$src2,$dest) ---*---

// Returns true iff
// 1) $src1 or $src2 is older than $dest
// 2) $dest does exists

function hasCache($src1,$src2,$dest) {
  return file_exists($dest) && (filemtime($src1) < filemtime($dest)) && (filemtime($src2) < filemtime($dest));
}





function getPercentage($x,$y) {
   return $y > 0 ? intval(10000*($x / $y))/100 : 0;
}
function printStatus($t) {
   global $base_url;
   $p1 = getPercentage($t[1],$t[1]+$t[2]);
   $p2 = getPercentage($t[-1],$t[-1]+$t[-2]);
   $status = ($t["1"] > 0 ? "0" : "1") . ($t["-1"] > 0 ? "0" : "1");
   print "<img alt=\"[$status]\" title=\"$p1% of pool and $p2% of non-pool documents to assess\" src=\"$base_url/img/status-$status.png\"/>";
}


/*

  Display header & footer

*/


function make_header($title = "") {
     global $menu, $is_root;
  global $no_topic, $id_topic, $url_add, $id_pool, $pool, $login;
  global $base_url, $xrains;
   global $header_done, $inex_user, $message;
   if ($header_done) return;
   $header_done = 1;


header("Content-type: application/xhtml+xml");
header("Content-encoding: UTF-8");
print '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0//EN" "http://www.w3.org/Math/DTD/mathml2/xhtml-math11-f.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:html="http://www.w3.org/1999/xhtml" xmlns:xrai="<?=$xrains?>" xmlns:xlink="http://www.w3.org/1999/xlink" xml:lang="en">
   <head>
      <title><?=$GLOBALS["main_title"]?> - <?=htmlspecialchars($title)?></title>
      <link rel="stylesheet" href="<?=$base_url?>/css/help.css" title="help"/>
      <link rel="stylesheet" href="<?=$base_url?>/css/style.css" />
   </head>
      <body id="body" onload="document_loaded()">
   <table id="menubar" class="localisation">
     <tr>
      <td><span style="color: #333333; ">User <b<? if ($is_root) print " style='color:red'"; ?>><?=$inex_user?> </b> </span> |
      <?

          // Menus
     if (is_array($menu)) {
       print "<span class='menubar'>";
       foreach($menu as $name => $items) {
       print "<a  id='a_menu_$name' class='menu' href='javascript:void(0)  ' onmouseover='show_menu(this,\"menu_$name\")'>$name</a> ";

     print "<div id='menu_$name' class='menu' onmouseout='menuout(event); return false;'>";
     foreach($items as $m)
       print "<div id='href $m[0]'><a onclick='hide_menu_id(\"menu_$name\")' href='$m[0]' title=\"" . htmlspecialchars($m[2]) . "\">$m[1]</a></div>";

     print "</div>";
     print " | ";
   }
   print "</span>";

   // Localisation
      $first = true; foreach($GLOBALS["localisation"] as $l) {
        if ($first) $first = false;  else print " <b>&gt;</b>   ";
        print " <a href='$l[1]' title=\""
        . htmlspecialchars($l[2]) . "\">" . htmlspecialchars($l[0]) . "</a> "; }

  }

   ?>
  </td>
  <td style="width: 0; text-align: right;">
  <?
    global $icons;
    if (sizeof($icons) > 0) {
      foreach($icons as $icon) {
         print "<a href='$icon[3]'  onclick='$icon[4]'><span><img src='$icon[1]' id='$icon[0]' title='$icon[2]'/>$icon[5]</span></a>";
      }
    }
  ?>
        <a onclick="right_panel('informations','img_informations','<?=$base_url?>/iframe/informations.php')"><span><img src="<?=$base_url?>/img/info" id="img_informations" alt="Informations (shift+i)"/><div class="help_top">Displays some information and the changelog of X-Rai.<br/><b>Shortcut</b>: hold <code>shift</code> and press <code>i</code></div></span></a>

       <a title="Help panel on/off" onclick="toggle_help()" class="<?=$_COOKIE["no_help"] > 0 ? "": "on"?>" id="a_help"><img src="<?=$base_url?>/img/question"/><div class="help_top">Toggle on or off the help tips</div></a>
      <iframe id="informations" class="right_panel"/>
      </td></tr>
   </table>
   <? if ($message) print "<div class='message'>$message</div>"; ?>
   <script type="text/javascript" language="javascript"><![CDATA[
var base_url = "<?=$base_url?>";
function popup_window(uri,w,h) {
window.open("<?=$base_url?>/" + uri,"inex-xrai-popup","toolbar=no,location=no,  directories=no,status=no,scrollbars=yes,menubar=no,copyhistory=no,width=" + w + ",height=" + h);
}

function show_colors() {
window.open("<?=$base_url?>/colours.php?id_pool=<?=$id_pool?>","inex_colors","toolbar=no,location=no,directories=no,status=yes,menubar=no,copyhistory=no,width=650,height=400");
}

function show_topic() {
window.open("<?=$base_url?>/show_topic.php?id_topic=<?=$id_topic?>","inex_topic","width=450,height=400,toolbar=no,location=no,directories=no,status=yes,menubar=no,copyhistory=no");
}
]]>
</script>

<table style="width: 100%; border-bottom: 2pt dashed #dddddd;"><tr>
<td style="vertical-align: middle; width: 100%; text-align: center;">

<div style="font-size: xx-large"><?=$GLOBALS["main_title"]?></div>
<div style="margin: 0 10%; font-size: large"><?=htmlspecialchars($title)?></div>
<div class="menu">
<script src="<?=$base_url?>/js/inex.js" language="javascript"></script>
<?
  // In pool

  print "</div></td>";
  print "<td style='text-align: right'><img src='$base_url/img/inex.jpg' alt='INEX'/></td></tr>";
  print "</table>";
}


function add_menu($menubar, $url, $title, $comment = "") {
   global $menu;
   $menu[$menubar][] = array($url,$title,$comment);
}
function add_icon($id, $url, $title, $href, $onclick, $helpstring) {
  global $icons;
  $icons[] = array($id, $url,$title, $href, $onclick, $helpstring);
}


function make_footer($exit = false) {
   print "</body></html>\n";
   if ($exit) exit;
}

function non_fatal_error($message, $dbg="") {
   global $do_debug;
   print "<div class='error'>$message</div>";
   if ($do_debug) print "<div>$dbg</div>";
}

function fatal_error($message, $dbg="") {
   global $do_debug;
   make_header("Error");
   print "<div class='error'>$message</div>";
   if ($do_debug) print "<div>$dbg</div>";
   make_footer();
   exit(1);
}

function error($message) {
   fatal_error($message);
}



function url_propagate($varname) {
   global $url_add;
   $url_add .= ($url_add ? "&amp;" : "") . rawurlencode("$varname") . "=" . rawurlencode($GLOBALS[$varname]);
}


/* Form specific functions */
function form_export($name) {
     print "<input type='hidden' name='$name' value='" . htmlentities($$name) . "'/>";
}
function form_hidden($name, $value) {
     print "<input type='hidden' name='$name' value='" . htmlentities($value) . "'/>";
}

function check_valid_pool() {
   global $id_pool;
   if ($id_pool > 0) return;
   header("Location: index.php");
   exit;
}

// OUTPUT BEGINS HERE !
// -----------------------------

require_once("./include/settings.inc");
$is_root = $root_users[$inex_user];

// Connection to database
$xrai_db = &DB::connect($xrai_dsn);
if (!DB::isError($xrai_db)) $xrai_db->setFetchMode(DB_FETCHMODE_ASSOC);
if (DB::isError($xrai_db)) exit("Database connection failed");
if ($db_schema) {
   $res = $xrai_db->query("set search_path=$db_schema, pg_catalog");
   if (DB::isError($res)) exit("Database connection failed");
}

$write_access = $can_modify = false;

if ($id_pool > 0) {
   url_propagate("id_pool");
   $pool = $xrai_db->getRow("SELECT * FROM $db_pools WHERE id=?",array($id_pool),DB_FETCHMODE_ASSOC);
   if (!$pool || DB::isError($pool)) exit("Cannot retrieve pool data" . ($do_debug ? ": " . $pool->getUserInfo() : ""));
   $id_topic = $pool["idtopic"];
   if (!$pool["name"]) $pool["name"] = "Topic $id_topic";
   $write_access = $can_modify = ($pool["enabled"] == 't');
   add_menu("Pool","javascript:show_topic()","Topic","View topic definition");
   add_menu("Pool","javascript:show_colors()","Keywords","Edit keyword highlighting");
}

if ($is_root) {
/*    add_menu("Admin","$base_url/admin/add_pool","Add a pool","Add a new pool");
    add_menu("Admin","$base_url/admin/manage_pools","Pools view","View pools (manage users, etc.)");*/
}


?>
