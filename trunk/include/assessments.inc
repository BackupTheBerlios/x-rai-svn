<?

// Assessments library


// ---------------------
// - XID <-> FILE,PATH -
// ---------------------


// --- id2path($file, $path) ---
// Retrieve the xid of an element given its path
// (volume or article relative)
// ----------------------

function path2record($file,$xpath) {
//    print "<b>$xpath</b><br>";
  if (empty($xpath)) $qh = mysql_query("SELECT m.* FROM map m, files f WHERE f.name = '$file'  AND m.xid = f.xid");
   else $qh = mysql_query("SELECT m.* FROM map m, files f, paths p WHERE f.name = '$file' AND p.path = '$xpath' AND m.path = p.id AND m.xid >= f.xid AND m.xid <= f.post");
   
   if (!$qh) return false;
   if (mysql_num_rows($qh) > 1) {
      print "<div class='error'>" . mysql_num_rows($qh)
      . " results for one path ($file#$xpath) : <br/>SELECT m$n.xid as xid $q_from $q_where</div>";
      make_footer(1);
   }
   $row = sql_fetch_array($qh);
   sql_free($qh);
   return $row;
}

function path2id($file,$xpath) {
    $a = path2record($file,$xpath);
    if (is_array($a)) return $a["xid"];
    return false;
}

// --- id2path($xid) ---
// Retrieve the file & the xpath of any element given its xid
// Returns an array of two elements (file, xpath)
// ----------------------

function id2path($xid) {
   $qh = do_query("select * from files WHERE xid <= $xid AND post >= $xid ORDER BY xid DESC LIMIT 0,1");
   $file = mysql_fetch_array($qh);
   sql_free($qh);
   if (!$file) return false;

   $qh = do_query("SELECT p.path FROM paths p, map m WHERE m.xid = $xid and p.id = m.path");
   $path = mysql_fetch_row($qh);
   sql_free($qh);
   return array($file["name"],$path);
}





function evaluation_string($ev2) {
  global $relevances, $coverages;
  return ($ev2 ? $relevances[$ev2[0]] . " - " . $coverages[$ev2[1]] . '"' : "No assessment");
}




/* Return the assessment link
  a assessment
  i inferred flag ?
  p in pool ?
  t todo ?
*/
function get_assessment_link($a, $i = false, $p = false, $t = true, $broken = false) {
  if ($a == "0") return "img/a0.png";
  if ($a == 'U') return "img/U.png";
  return "img/a$a.png";

  if ($broken) return "img/I.png";
  if ($a == 'U') return ($t ? "img/U.png" : "img/na.png");
  return "img/" . ($i ? "i" : "a") . "$a.png";
/*  if ($t || $p || !$i) return "img/" . ($i ? "i" : "a") . "$a.png";
  return "img/na.png";*/
}

function get_assessment_img($a, $i = false, $p = false, $t = true, $broken = false) {
  global $assessments, $base_url;
  if (!$a) $a = "U";
  return "<img width='18' height='12' "
    . " src='$base_url/" . get_assessment_link($a,$i,$p,$t,$broken) . "'"
    . " title='" . $assessments[$a]  . ($i ? " [I]" : "") . "'"
//     . " title='" .  ($t || $p || !$i ? $assessments[$a] : $assessments["U"]) . ($i ? " [I]" : "") . "'"
    . ' />';
}

function get_evaluation_img($a, $i = false, $p = false, $t = true, $broken = false, $add_string ="") {
  global $assessments, $base_url;
  if (!$a) $a = "U";
  return "<img $add_string width='18' height='12' alt='A'"
    . " src='$base_url/" . get_assessment_link($a,$i,$p,$t,$broken) . "'"
    . " title='" . $assessments[$a]  . ($i ? " [I]" : "") . "'"
//     . " title='" .  ($t || $p || !$i ? $assessments[$a] : $assessments["U"]) . ($i ? " [I]" : "") . "'"
    . ' />';
}

function get_element_link(&$e) {
  if ($e->is_inconsistant()) return "img/I.png";
  if ($e->is_unknown()) return ($e->is_to_assess() ? "img/U.png" : "img/na.png");
  return "img/" . ($e->is_inferred() ? "i" : "a") . $e->get_assessment() . ".png";
}

function get_element_img(&$e) {
   return "<img width='18' height='12' "
    . " src='" . get_element_link($e) . "'"
    . " title='" . $assessments[$e->get_assessment()]  . ($e->is_inferred() ? " [I]" : "")
      . ($e->is_inconsistant() ? " [inconsistant]" : "")
      . "'"
    . ' />';
}

/** Access to the files */
class Files {
     function getFileId($collection,$filename) {
      global $xrai_db, $db_files;
      $id = $xrai_db->getOne("SELECT id FROM $db_files WHERE collection=? AND filename=?",array($collection,$filename));
      if (!$id) {
         $id = $xrai_db->nextId("{$db_files}_id");
         if (!DB::isError($id)) {
            $res = $xrai_db->autoExecute("$db_files",array("id" => $id, "collection" => $collection, "filename" => $filename));
            if (DB::isError($res)) return $res;
         }
      }
      return $id;
     }
}

/** Access to the paths db */
class Paths {
     function getPathId($path) {
      global $xrai_db, $db_paths;
      if (!is_string($path)) $path = "";
      $id = $xrai_db->getOne("SELECT id FROM $db_paths WHERE path=?",array($path));
      if (!$id) {
         $id = $xrai_db->nextId("{$db_paths}_id");
         if (!DB::isError($id)) {
            $res = $xrai_db->autoExecute("$db_paths",array("path" => $path, "id" => $id));
            if (DB::isError($res)) return $res;
         }
      }
      return $id;
     }
}

/** Access to the assessments db */
class Assessments {
   function Assessments($idPool, $idFile, $version, $status) {
      $this->version = $version;
      $this->idPool = $idPool;
      $this->idFile = $idFile;
      $this->status = $status;
   }

   function create($idPool, $collection, $file) {
      global $db_aversion, $db_assessments, $xrai_db, $db_toassess;
      $idFile = Files::getFileId($collection,$file);
      if (DB::isError($idFile)) return $idFile;

      $version = $xrai_db->getOne("SELECT version FROM $db_aversion WHERE file=? AND pool=?",array($idFile,$idPool));
      if (DB::isError($version)) return $version;
      if (!$version) $version = 0;
      
      $status = $xrai_db->getOne("SELECT status FROM $db_toassess WHERE idfile=? AND idpool=?",array($idFile,$idPool));
      if (DB::isError($status)) return $status;
      if (!$status) $status = 0;

      return new Assessments($idPool, $idFile, $version, $status);
   }

   function &getCursor() {
      global $xrai_db, $db_assessments, $db_aview;
      return $xrai_db->query("SELECT assessments.exhaustivity, assessments.startPath, assessments.endPath,"
               . " assessments.idpool AS idPool, files.collection, files.filename, pathsstart.path AS startxpath, pathsend.path AS endxpath"
               . " FROM assessments JOIN paths pathsstart ON assessments.startpath = pathsstart.id"
               . " JOIN paths pathsend ON assessments.endpath = pathsend.id JOIN files ON assessments.idfile = files.id"
               . " WHERE idfile=? AND idpool=?",array($this->idFile, $this->idPool));
   }

   function add($a, $start, $end) {
      global $xrai_db, $db_assessments;
      $startid = Paths::getPathId($start); if (DB::isError($startid)) return $startid;
      $endid = Paths::getPathId($end); if (DB::isError($endid)) return $endid;
      
      return $xrai_db->autoExecute($db_assessments, array("idpool" => $this->idPool, "exhaustivity" => $a, "idfile" => $this->idFile,
               "startpath" => $startid, "endpath" => $endid));
   }
   function modify($a, $start, $end) {
      global $xrai_db, $db_assessments;
      $startid = Paths::getPathId($start); if (DB::isError($startid)) return $startid;
      $endid = Paths::getPathId($end); if (DB::isError($endid)) return $endid;
      return $xrai_db->query("UPDATE $db_assessments SET exhaustivity=? WHERE idpool=? AND idfile=? AND startpath=? AND endpath=?", array($a,$this->idPool, $this->idFile, $startid, $endid));
   }

   function remove($start,$end) {
      global $xrai_db, $db_assessments;
      $startid = Paths::getPathId($start); if (DB::isError($startid)) return $startid;
      $endid = Paths::getPathId($end); if (DB::isError($endid)) return $endid;
      return $xrai_db->query("DELETE FROM $db_assessments WHERE idpool=? AND idfile=? AND startpath=? AND endpath=?", array($this->idPool, $this->idFile, $startid, $endid));
   }


   function setDone($flag) {
      global $xrai_db, $db_toassess;
      $new_status = 0;
      if ($flag) {
         if ($this->status == 1) $new_status = 2;
         if ($this->status == -1 || $this->status == 0) $new_status = -2;
      } else { 
         if ($this->status == 2) $new_status = 1;
         if ($this->status == -2 || $this->status == 0) $new_status = -1;
      }
      if ($new_status != $status) {
         return $xrai_db->query("UPDATE $db_toassess SET status=? WHERE idpool=? AND idfile=?",array($this->idPool, $this->idFile));
      }
   }

   function setNextVersion() {
      global $db_aversion, $db_assessments, $xrai_db;
      $this->version++;
      if ($this->version > 1)
         return $xrai_db->autoExecute($db_aversion,array("version" => $this->version),DB_AUTOQUERY_UPDATE," file={$this->idFile} AND pool={$this->idPool}");
      return $xrai_db->autoExecute($db_aversion,array("version" => $this->version, "file" => $this->idFile, "pool" => $this->idPool));
   }

   function getVersion() { return $this->version; }

}


?>
