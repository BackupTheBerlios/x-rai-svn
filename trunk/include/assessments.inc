<?



/* Return the assessment link
  a assessment
  i inferred flag ?
  p in pool ?
  t todo ?
*/
function get_assessment_link($a) {
  return "img/a$a.png";
}

/** Access to the files */
class Files {
     function getFileId($collection,$filename, $create = false) {
      global $xrai_db, $db_files;
      $id = $xrai_db->getOne("SELECT id FROM $db_files WHERE lower(collection)=lower(?) AND lower(filename)=lower(?)",array($collection,$filename));
      if (!$id) {
         if (!$create) return -1;
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
     function getPathId($path, $create = false) {
      global $xrai_db, $db_paths;
      if (!is_string($path)) $path = "";
      $id = $xrai_db->getOne("SELECT id FROM $db_paths WHERE path=?",array($path));
      if (!$id) {
         if (!$create && $path != "") {
            print "<div>Path $path not found</div>\n";
            return -1;
         }
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
   function Assessments($idPool, $idFile, $version, $status, $inpool, $hasrelevant) {
      $this->version = $version;
      $this->idPool = $idPool;
      $this->idFile = $idFile;
      $this->status = $status;
      $this->inpool = $inpool;
      $this->hasRelevant = $hasrelevant;
   }

   function create($idPool, $collection, $file) {
      global $db_filestatus, $db_assessments, $db_true, $xrai_db, $db_toassess;
      $idFile = Files::getFileId($collection,$file);
      if (DB::isError($idFile)) return $idFile;

      $statusrow = $xrai_db->getRow("SELECT version, status, inpool, hasrelevant FROM $db_filestatus WHERE idfile=? AND idpool=?",array($idFile,$idPool),DB_FETCHMODE_ROW);
      if (DB::isError($statusrow)) return $statusrow;

      if (!is_array($statusrow)) {
         $version = 0;
         $status = 0;
         $inpool = false;
      } else {
         list($version,$status,$inpool,$hasrelevant) = $statusrow;
         $inpool = ($db_true == $inpool);
         $hasrelevant= $db_true == $hasrelevant;
      }
      return new Assessments($idPool, $idFile, $version, $status, $inpool, $hasrelevant);
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

      return $xrai_db->autoExecute($db_assessments, array("idpool" => $this->idPool, "exhaustivity" => ($a == "null" ? null : $a), "idfile" => $this->idFile,
               "startpath" => $startid, "endpath" => $endid));
   }
   function modify($a, $start, $end) {
      global $xrai_db, $db_assessments;
      $startid = Paths::getPathId($start); if (DB::isError($startid)) return $startid;
      $endid = Paths::getPathId($end); if (DB::isError($endid)) return $endid;
      return $xrai_db->query("UPDATE $db_assessments SET exhaustivity=? WHERE idpool=? AND idfile=? AND startpath=? AND endpath=?", array(($a == "null" ? null : $a),$this->idPool, $this->idFile, $startid, $endid));
   }

   function remove($start,$end) {
      global $xrai_db, $db_assessments;
      $startid = Paths::getPathId($start); if (DB::isError($startid)) return $startid;
      $endid = Paths::getPathId($end); if (DB::isError($endid)) return $endid;
      return $xrai_db->query("DELETE FROM $db_assessments WHERE idpool=? AND idfile=? AND startpath=? AND endpath=?", array($this->idPool, $this->idFile, $startid, $endid));
   }

   function getDone() {
      return $this->status;
   }

   function setStatus($status) {
      global $xrai_db, $db_filestatus;
      if ($this->status != $status) {
         $this->status = $status;
         return  $xrai_db->autoExecute($db_filestatus,array("status" => $this->status),DB_AUTOQUERY_UPDATE," idfile={$this->idFile} AND idpool={$this->idPool}");
      }
      return true;
   }

   function setHasRelevant($hasrelevant) {
      global $xrai_db, $db_filestatus;
      if ($this->hasrelevant != $hasrelevant) {
         $this->hasrelevant = $hasrelevant;
         return  $xrai_db->autoExecute($db_filestatus,array("hasrelevant" => $this->hasrelevant ? "true" : "false"),DB_AUTOQUERY_UPDATE," idfile={$this->idFile} AND idpool={$this->idPool}");
      }
      return true;
   }

   function setNextVersion() {
      global $db_filestatus, $db_assessments, $xrai_db;
      $this->version++;
      if ($this->version > 1)
         return $xrai_db->autoExecute($db_filestatus,array("version" => $this->version),DB_AUTOQUERY_UPDATE," idfile={$this->idFile} AND idpool={$this->idPool}");
      return $xrai_db->autoExecute($db_filestatus,array("version" => $this->version, "idfile" => $this->idFile, "idpool" => $this->idPool));
   }

   function getVersion() { return $this->version; }

}


?>
