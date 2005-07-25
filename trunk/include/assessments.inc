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

// Returns the path to $xid
// Path is an array of couples (xid,post) that goes from the root (within $range)
// to the given $xid
function get_path($xid, $range = false) {
   if ($range) $qrange = " AND xid >= $range[0] AND xid <= $range[1] ";
   $qh = do_query("SELECT * FROM map WHERE xid <= $xid AND post >= $xid $qrange ORDER BY xid ");
   while ($r = sql_fetch_array($qh)) $a[] = $r;
   return $a;
}




// ---------------------------------
// ------- Class Element --------
// ---------------------------------

$nomask = 2047;
$emask = Array("min" => Array( 2047, 2045, 2017, 1793), "max" => Array(3, 31, 255, 2047), "val" => Array(2, 28, 224, 1792));
$smask = Array("min" => Array(2047, 2045, 1753, 1169), "max"=>Array(3, 295, 879, 2047), "val" => Array( 2, 292, 584, 1168));

define("ELEMENT_IN_POOL",1);
define("ElEMENT_INFERRED_ASSESSMENT",2);
define("ELEMENT_TO_ASSESS",4);
define("ELEMENT_ASSESSMENT_CHANGED",8);
define("ELEMENT_MASK_CHANGED",16);
define("ELEMENT_IN_DATABASE",32);
define("ELEMENT_DATABASE_INCONSISTANT",64);

define("S_MIN",0);
define("S_MAX",3);
define("E_MIN",0);
define("E_MAX",3);

define("MINE",0);
define("MAXE",1);
define("MINS",2);
define("MAXS",3);

class Element {
   var $tag, $rank, $xid, $post; // Identification
   var $parent; // Parent reference
   var $children; // Children (loaded)
   var $number_of_children; // Number of children (total)
        var $mask; // Mask
   var $state; // State

   // Constructor
   function Element($record) {
         global $nomask;
    $this->tag = $record["tag"];
    $this->rank = $record["rank"];
    $this->xid = $record["xid"];
    $this->post = $record["post"];
         $this->mask = $nomask;
    $this->parent = false;
    $this->number_of_children = $record["children_count"];
    $this->children = array();
    $this->state = 0;
    $this->assess = 'U';
    $this->set_database_inconsistant($record["inconsistant"] == 'Y');
//       print "Constructed $this->tag[$this->rank] ($this->xid,$this->post)<br>\n";
   }


   // Getters

   function get_name() {
      return $this->assess;
/*    $assessments[$e->get_assessment()]  . ($e->is_inferred() ? " [I]" : "")
      . ($e->is_inconsistant() ? " [inconsistant]" : "")*/
   }
   
   function get_image() {
     return get_element_img($this);
   }

   function get_relative_path() {
      return ($this->parent ? $this->parent->get_relative_path() . "/" . $this->tag . "[" . $this->rank . "]" : "");
     }

   // get assessment "with" to assess => "A"
   function get_assessment_wta() {
      return ($this->is_to_assess() && $this->assess == 'U' ? "A" : $this->assess);
   }
   
   function get_assessment() {
      return $this->assess;
   }

   // --- Debug ---

   function get_state() {
      return get_element_img($this) . " ["
      . ($this->is_in_pool() ? "P" : "")
      . ($this->is_inferred() ? "I" : "")
      . ($this->is_to_assess() ? "T" : "")
     . ($this->is_inconsistant() ? "X" : "")
     . ($this->is_database_inconsistant() ? "<span title='inconsistant'>Y</span>" : "")
     . ($this->is_to_change() ? "<span title='to change'>c</span>" : "")
     . ($this->is_to_add() ? "<span title='to add'>a</span>" : "")
     . ($this->is_to_delete() ? "<span title='to delete'>d</span>" : "")
    . ($this->has_assess_changed() ? "<span title='has changed'>*</span>" : "")
    . ($this->has_other_children() ? "": "<span title='no other child'>@</span>") 
      . "/$this->state] " . $this->get_assessment()
     . ", child=" .  sizeof($this->children) . "/" . $this->number_of_children
     . " e in (" . $this->emin() . "," . $this->emax() . ")"
     . " s in (" . $this->smin() . "," . $this->smax() . ") :"
     . " (" . $this->mask . ")";
   }

   // --- Children ---

   // Append a child ($row is a SQL row result) and return the new child
     function &append_child_from_sql(&$row) {
       $this->children[] = new Element($row);
       $this->children[sizeof($this->children)-1]->parent = &$this;
      return $this->children[sizeof($this->children)-1];
     }

   function has_other_children() {
      return $this->number_of_children > sizeof($this->children);
   }



   // --- States  ---

   function is_in_pool() { return ELEMENT_IN_POOL & $this->state; }
   function is_inferred() { return ElEMENT_INFERRED_ASSESSMENT & $this->state; }
   function is_to_assess() { return (ELEMENT_TO_ASSESS & $this->state) || $this->is_in_pool(); }
   function is_in_database() { return ELEMENT_IN_DATABASE & $this->state; }
   function has_assess_changed() { return ELEMENT_ASSESSMENT_CHANGED & $this->state; }
   function has_mask_changed() { return ELEMENT_MASK_CHANGED & $this->state; }
   function is_database_inconsistant() { return ELEMENT_DATABASE_INCONSISTANT & $this->state; }
   function is_unknown() { return $this->assess == 'U'; }

   function set($v, $flag) {
      if ($flag) $this->state |= $v;
      else $this->state &= ~$v;
   }
   function set_in_pool($flag) { $this->set(ELEMENT_IN_POOL, $flag); }
   function set_inferred($flag) { $this->set(ElEMENT_INFERRED_ASSESSMENT, $flag); }
   function set_to_assess($flag) { $this->set(ELEMENT_TO_ASSESS, $flag); }
   function set_in_database($flag) { $this->set(ELEMENT_IN_DATABASE, $flag); }
   function set_mask_changed($flag) { $this->set(ELEMENT_MASK_CHANGED, $flag); }
   function set_assess_changed($flag) { $this->set(ELEMENT_ASSESSMENT_CHANGED, $flag); }
   function set_database_inconsistant($flag) { $this->set(ELEMENT_DATABASE_INCONSISTANT, $flag); }

   // --- Actions to do ---
   function is_to_delete() { return $this->is_in_database() && !$this->is_to_assess() && $this->is_unknown(); }
  function is_to_keep() { return ($this->is_to_assess() || !$this->is_unknown()); }
   function is_to_add() { return (!$this->is_in_database()) && $this->is_to_keep(); }
   function is_to_change() {
      return ($this->is_in_database()) && $this->is_to_keep()
         && ($this->has_assess_changed() || ($this->is_inconsistant() xor $this->is_database_inconsistant()));
   }
   function has_changed() {
      return $this->is_to_delete() || $this->is_to_add() || $this->is_to_change() || $this->has_mask_changed();
   }
   function is_inconsistant() {
      return ($this->emin() > $this->emax()) || ($this->smin() > $this->smax())
            || ($this->assess != 'U' &&
               (($this->assess[0] > $this->emax()) || ($this->assess[0] < $this->emin())
               || ($this->assess[1] > $this->smax()) || ($this->assess[1] < $this->smin()))
               );
   }


   // --- Update database and mask ---

   function print_changes() {
      if ($this->has_changed()) {
         print "<div title='" . $this->xid. "'><b>";
         if ($this->is_to_delete()) print "delete";
         if ($this->is_to_add()) print "add";
         if ($this->is_to_change()) print "update";
         if ($this->has_mask_changed()) print " [MASK]";
         print "</b> " . $this->get_relative_path() . " "
         . $this->get_state($changes[$i][1])
         . "</div>";
      }
      for($i=0; $i < sizeof($this->children); $i++) $this->children[$i]->print_changes();
   }

   function sql_query($query) {
      global $do_debug, $checking_pool;
       if ($checking_pool) print "$query\n";
    else if ($do_debug) print "<div style='color: #cccccc;'>$query</div>";
      sql_query($query);
   }

   function update_database($stop_on_error = true) {
      global $id_pool, $db_assessments;
//       if ($this->is_inconsistant()) {
//          if ($stop_on_error) return false;
//       }

      if ($this->is_to_delete()) {
          $query = "where id_pool=$id_pool AND xid=" . $this->xid . " AND in_pool='N'";
          $this->sql_query("DELETE from $db_assessments $query");
//          $this->sql_query("DELETE from assessments_backup $query");
      } elseif ($this->is_to_add()) {
          $query = "(id_pool,xid,assessment,inferred,inconsistant) "
               . "VALUES ($id_pool,"
              . $this->xid
              . ",'" . $this->get_assessment()
              . "','" . ($this->is_inferred() ? 'Y' : 'N')
            . "','" . ($this->is_inconsistant() ? 'Y' : 'N')
            . "'"
            . ")";
         $this->sql_query("INSERT INTO $db_assessments $query");
//         $this->sql_query("INSERT INTO assessments_backup $query");
      } elseif ($this->is_to_change()) {
           $query = "SET "
            . "assessment='" . $this->get_assessment() . "',"
         . "inferred='" . ($this->is_inferred() ? 'Y' : 'N'). "',"
         . "inconsistant='" . ($this->is_inconsistant() ? 'Y' : 'N') . "'"
         . " WHERE id_pool=$id_pool AND xid=" . $this->xid;
         $this->sql_query("UPDATE $db_assessments $query");
//         $this->sql_query("UPDATE assessments_backup $query");
         $n = mysql_affected_rows();
         if ($n != 1) {
            sql_query("ROLLBACK");
            fatal_error("$n affected rows in query '$query'");
         }
      }

      for($i=0; $i < sizeof($this->children); $i++)
         if (!$this->children[$i]->update_database($stop_on_error) && $stop_on_error) return false;

      return true; // success
   }

   function update_masks() {
      $p = $this->get_relative_path();
  ?>
    if (e = d.getElementById('<?=$this->xid?>')) {
      e.setAttribute('name',"<?=$this->get_assessment_wta()?>");
    <?
      if ($this->is_to_assess()) print "e.setAttribute('ta','yes');\n"; else print "e.removeAttribute('ta');\n";
      if ($this->is_inferred()) print "e.setAttribute('ii','yes');\n"; else print "e.removeAttribute('ii');\n";
      if ($this->is_inconsistant()) print "e.setAttribute('ic','yes');\n"; else print "e.removeAttribute('ic');\n";
    ?>
     }
    <?
      if ($do_debug) print "else  alert(\"Error: " . $this->xid . " is not in document. Please reload document view\");\n";  
      for($i=0; $i < sizeof($this->children); $i++) $this->children[$i]->update_masks();
   }

   // --- Mask ---

   
   // ---- Inference ----
   function e_range() {
      global $emask;
      $r = Array(3, 0);
        $i = 0;
          while ((($emask["val"][$i] & $this->mask) == 0) && $i <= 3) { $i++; } $r[0] = $i;
            while (($emask["val"][$i] & $this->mask) && $i <= 3) { $r[1] = $i; $i++; }
        return $r;
   }
   
   function s_range() {
      global $smask;
      $r = Array(3, 0);
        $i = 0;
          while ((($smask["val"][$i] & $this->mask) == 0) && $i <= 3) { $i++; } $r[0] = $i;
            while (($smask["val"][$i] & $this->mask) && $i <= 3) { $r[1] = $i; $i++; }
        return $r;
   }

 
  function emin() { $r = $this->e_range(); return $r[0]; } 
  function emax() { $r = $this->e_range(); return $r[1]; } 
  function smin() { $r = $this->s_range(); return $r[0]; } 
  function smax() { $r = $this->s_range(); return $r[1]; } 
 
  function get_e_min() {
    if ($this->assess == 'U' || $this->is_inferred()) { $r = $this->e_range(); return $r[0]; }
    return $this->assess[0];
  }
  function get_e_max() {
    if ($this->assess == 'U' || $this->is_inferred()) { $r = $this->e_range(); return $r[1]; }
    return $this->assess[0];
  }
  function get_s_min() {
    if ($this->assess == 'U' || $this->is_inferred()) { $r = $this->s_range(); return $r[0]; }
    return $this->assess[1];
  }
  function get_s_max() {
    if ($this->assess == 'U' || $this->is_inferred()) { $r = $this->s_range(); return $r[1]; }
    return $this->assess[1];
  }
  function get_ranges() {
     if ($this->assess == 'U' || $this->is_inferred()) { return array_merge($this->e_range(),$this->s_range()); }
     return Array($this->assess[0],$this->assess[0],$this->assess[1],$this->assess[1]);
  }


   function reset() {
         global $nomask;
      $this->mask = $nomask;
      for($i=0; $i < sizeof($this->children); $i++) $this->children[$i]->reset();
   }
   
   function set_max_e($e) {
      global $emask;
      if ($e >= E_MAX || $e < E_MIN) return;
      $this->mask &= $emask["max"][$e];
   }
   function set_min_e($e) { 
      global $emask;
      if ($e > E_MAX || $e <= E_MIN) return;
      $this->mask&= $emask["min"][$e];
   }
   
   function set_max_s($s) {
      global $smask;
      if ($s >= S_MAX || $s < S_MIN) return;
      $this->mask&= $smask["max"][$s];
   }
   function set_min_s($s) { 
      global $smask;
      if ($s > S_MAX || $s <= S_MIN) return;
      $this->mask &= $smask["min"][$s];
   }
   
   // Inference
   function inference($first_pass = true) {
         global $nomask, $emask, $smask;
         if ($first_pass) $this->mask = $nomask;
         $omask = $this->mask;
         do {
          $mask = $this->mask;
           
           // Process parent
           if ($this->parent) {
             $p_ranges = $this->parent->get_ranges();
             $this->set_max_e($p_ranges[MAXE]); // Rule 1.1
/*             if ($p_ranges[MINS] == S_MAX && $p_ranges[MAXE] > (E_MIN+1) && $this->number_of_children > 1) 
               $this->set_max_e($p_ranges[MAXE] - 1); // Rule 6.1*/
           }

        if (sizeof($this->children) > 0) { 
           // Process children (pass 1)
           $e_sum_min = 0; // children exh. sum
           $e_sum_max = 0;
           $e_min_max = E_MIN;
           
           $children_s_min = S_MAX; $children_s_max = S_MIN; // max & min
           $s_superior_child = -2; // The only s-superior child
           $s_inferior_child = -2; // The only s-inferior child
           
           $is_highly_specific = ($this->get_s_min() == S_MAX) && $this->get_e_min() > (E_MIN+1);
           
           for($i=0; $i < sizeof($this->children); $i++) {
             $child = &$this->children[$i];
             if ($first_pass || $this->mask != $mask) $child->inference($first_pass);
             list($c_emin, $c_emax, $c_smin, $c_smax) = $child->get_ranges();
             $e_sum_max += $c_emax;
             $e_sum_min += $c_emin;
            $oomask = $this->mask; $olde = $this->get_e_min();
             $this->set_min_e($c_emin); // Rule 1.1
//                print "<div>Rule 1.1 for {$this->xid}, {$this->tag}: min e <- ? $c_emin => " . $this->get_e_min() . " / {$this->mask} vs $oomask ($olde) </div>";
             if ($children_s_min > $c_smin) $children_s_min = $c_smin;
             if ($children_s_max < $c_smax) $children_s_max = $c_smax;

             if ($c_emin > $e_min_max) $e_min_max = $c_emin;
             // For rule 5 
             if ($s_inferior_child != -1 && ($this->get_s_max() >= $c_smin))
                if ($s_inferior_child == -2) $s_inferior_child = $i; else $s_inferior_child = -1;
             // For rule 3 
             if ($s_superior_child != -1 && ($this->get_s_min() <= $c_smax))
                if ($s_superior_child == -2) $s_superior_child = $i; else $s_superior_child = -1;
           }
           
/*           if ($this->number_of_children > 1) {
             // Rule 6.2
             if ($e_min_max >= 2) $this->mask &= ~($emask["val"][2] & $smask["val"][3]);
             if ($e_min_max >= 3) $this->mask &= ~($emask["val"][3] & $smask["val"][3]);
           }*/
           
           // Process children (pass 2)
           if ($this->number_of_children == 0) {
             $this->set_max_s(S_MIN);
             $this->set_max_e(E_MIN);
           } else if (!$this->has_other_children()) {
             $this->set_max_s($children_s_max); // Rule 3.1
             $this->set_min_s($children_s_min); // Rule 5.1
             $this->set_max_e($e_sum_max); // Rule 4.1

             $child_emin = $this->get_e_min() - $e_sum_max;
             for($i=0; $i < sizeof($this->children); $i++) {
               $child = &$this->children[$i];
               $cmask = $child->mask;
               $e = $child_emin + $child->get_e_max();
               $child->set_min_e($e); // Rule 4.2
             // Rule 5.2
             if ($s_superior_child == $i) {
//                   print "[R5] Set min(specificity)=" . $this->get_s_min() . " for " . $child->get_relative_path() . "<br/>";
                  $child->set_min_s($this->get_s_min());
             } 
             // Rule 3.2
             if ($s_inferior_child == $i) {
//                   print "[R3] Set max(specificity)=" . $this->get_s_max() . " for " . $child->get_relative_path() . "<br/>";
                  $child->set_max_s($this->get_s_max()); 
             } 
             if ($cmask != $child->mask) $child->inference(false);
           }
           } // Has all children
         } // Has one child at least
         
           // End of loop
           $first_pass = false;
         } while ($this->mask != $mask);

         return ($this->mask != $omask);

   }

   // Todo + assessment
   function final_inference($needed = false) {
          $r = $this->get_ranges();
      if ($r[MINE] == $r[MAXE] && $r[MINS] == $r[MAXS]) {
         $new_assessment = $r[MINE] . $r[MINS];
          if ($this->assess == 'U' || ($this->is_inferred() && $new_assessment != $this->assess)) {
            $this->assess = $new_assessment;
            $this->set_inferred(true);
            $this->set_assess_changed(true);
         }
      } else if ($this->is_inferred()) {
         $this->assess = 'U';
         $this->set_assess_changed(true);
         $this->set_inferred(false);
      }
      
      if ($this->parent) {
          $a_p = $this->parent->assess;
          $is_todo =  ($a_p != "00" && $a_p != "U" && $needed);
      } else $is_todo = false;
      
//       $needs_children = false;

      
        $needs_children = !($this->assess == "U" || $this->assess == "00" || $this->assess == "13");
      
   // Old rules: compute the e-sum below
        if ($needs_children) {
        $e_sum = 0;
        for($i=0; $i < sizeof($this->children); $i++) 
           if ($this->children[$i]->assess != "U") $e_sum += $this->children[$i]->assess[0];
        if ($e_sum >= $this->assess[0]) $needs_children = false;
        
      }
        // New rules
        
      for($i=0; $i < sizeof($this->children); $i++) $is_todo |= $this->children[$i]->final_inference($needs_children);
      $this->set_to_assess($is_todo);
      return ($this->assess == "U" && $is_todo) || ($this->assess != "00" && $this->assess != "U") ;
   }

}




// ----------------------------------
// ------- Class Assessments --------
// ----------------------------------

class Assessments {

  /** Our data : an array (see defines above) */
  var $data;
  /** File */
  var $file;
  /** Restricted base path */
  var $basepath;
  /** Hash map for in table xids */
  var $assessed_xid;


    /* Print (debug) */
   function get_state(&$current) {
      return $current->get_state();
   }

    function print_debug_r(&$current) {
      print "<div style='margin-left: 0.3cm; padding-left: 3pt; border-left: 1pt solid #bbbbbb;'>"
        . "<span title='XID = $current->xid'"
      . ($current->is_in_database() ? " style='font-weight: bold;' " : "")
      . ">$current->tag[$current->rank] "
      . $this->get_state($current)
       . "</span>";
      for($i = 0; $i <sizeof($current->children); $i++)
        $this->print_debug_r($current->children[$i]);
      print "</div>";
    }

    function print_debug() {
     print "<div style='border: 1pt solid green; padding: 5pt'>";
     print "<div><b>Assessment tree</b></div>";
      $this->print_debug_r($this->data);
     print "</div>";
    }

    /** Get an array with statistics */
    function get_statistics_r(&$e, &$s) {
      if (($e->is_to_assess() && $e->assess == 'U') || $e->is_inconsistant()) {
        $s["TODO"][] = &$e;
     }
      if ($e->is_to_assess() || !$e->is_unknown()) $s[($e->is_inconsistant() ? "I" : $e->assess)] += 1;
      for($j = 0; $j < sizeof($e->children); $j++) {
        $this->get_statistics_r($e->children[$j],$s);
      }
    }
    function get_statistics() {
      $this->get_statistics_r($this->data,$s);
      return $s;
    }


    /** Constructor */
    function Assessments($id_pool, $file, $rootpath, $basepath) {
       global $db_assessments;
        // Retrieve (SQL) evaluated result (elements to assess)
       $root = path2record($file,$rootpath);
      $this->file = $file;
      $this->basepath = $path;
      if (!$root)
        fatal_error("Can't retrieve assessments ($file, $path)");

     $fields = "m.xid, m.parent, m.children_count, m.tag, m.rank, m.post";
       $query = "(SELECT a.assessment, a.in_pool, a.inferred, a.inconsistant, $fields"
          . " FROM $db_assessments a, map m where id_pool=$id_pool and m.xid = a.xid"
            . " AND a.xid >= $root[xid] AND a.xid <= $root[post])"
        . " UNION ("
        . "SELECT null, 'N', 'N', 'N', $fields"
        . " FROM $db_assessments a, map m where id_pool=$id_pool and m.parent = a.xid"
            . " AND a.xid >= $root[xid] AND a.xid <= $root[post]"
          . ") ORDER BY xid";
//            print "<div>" . htmlspecialchars($query) . "</div>";
        $result = do_query($query);

      $this->data = new Element($root);
      $current = &$this->data;

      // Construct the assessment tree
        while ($row = sql_fetch_array($result)) {
//          print "<div style='color: blue'>$row[xid]</div>";
        // Search for the good ancestor
        while (!($current->xid <= $row["xid"] && $current->post >= $row["xid"])) {
          if (!$current->parent) fatal_error("Can't construct assessment tree (1) : current range=($current->xid,$current->post) and xid=$row[xid] / Root is ($root[xid],$root[post])");
          $current = &$current->parent;
//           print "<div style='color: blue'>Going up to " . $current->parent[2] . "</div>";
        }
//          print "<div style='color: blue'>Found $current[2] (" . $current->post . ")</div>";

        // Retrieve elements between this ancestor and ourselves
        if ($current->xid != $row["xid"])
          if ($current->xid == $row["parent"]) {
            // Parent
            $current = &$current->append_child_from_sql($row);
          } else {
            $path = get_path($row["xid"],array($current->xid,$current->post));
            if (!$path || $path[0]["xid"] != $current->xid)
              fatal_error("Can't construct assessment tree (1) : " . $path[0]["xid"] . " <> " . $current->xid);
            for($i = 1; $i < sizeof($path); $i++)
              $current = &$current->append_child_from_sql($path[$i]);
            if ($current->xid != $row["xid"])
              fatal_error("Can't construct assessment tree (3): " . $current->xid . "!=" . $row["xid"]);
          }


           if ($row["assessment"]) {
              $current->assess = $row["assessment"];
              $this->assessed_xid[$row["xid"]] = true;
            $current->set_in_database(true);
            $current->set_to_assess(true);
            if ($row["in_pool"] == 'Y') $current->set_in_pool(true);
            if ($row["inferred"] == 'Y') $current->set_inferred(true);
            if ($row["inconsistant"] == 'Y') $current->set_database_inconsistant(true);
         }
      }
      sql_free($result);
//       $this->print_debug();
  }



  function get_relative_path(&$data) {
     return $data->get_relative_path();
  }

  /** Set an assessment,
    returns an array of changes
    each element in the array is an array with
      (0) "up" (update in assessment + mask), "mask" (update in mask), "del" (removing the assessment)
      (1) Element reference
  */
  function add_assessments($paths) {
     foreach($paths as $path => $a) {
    // Get the assessed node and its children,  add them
    if (is_integer($path)) $xid = $path;
    else {
      $r = path2record($this->file,$this->basepath . $path);
      if (!$r) fatal_error("Can't find $this->file#$this->basepath$path");
      $xid = $r["xid"];
    }
    $path = get_path($xid,array($this->data->xid, $this->data->post));

    if ($path[0]["xid"] != $this->data->xid)
      fatal_error("Roots are different:  $path[xid] and " . $this->data->xid);
    $current = &$this->data;
    $insert = false;
    for($i = 1; $i < sizeof($path); $i++) {
      $children = &$current->children;
      $j = 0;
      for(; $j < sizeof($children); $j++)
        if ($children[$j]->xid == $path[$i]["xid"]) break;
      if ($j < sizeof($children)) $current = &$children[$j];
      else {
        $current = &$current->append_child_from_sql($path[$i]);
        $insert = true;
      }
    }

   // Get its children and add them
   $children = &$current->children;
   $notin = "";
   for($j = 0; $j < sizeof($children); $j++)
      $notin .= ($notin ? "," : " AND xid NOT IN (") . $children[$j]->xid;
   if ($notin) $notin .= ")";
   $qh = do_query("SELECT * from map where parent=$xid $notin ORDER BY xid");
   while ($row = sql_fetch_array($qh)) {
      $current->append_child_from_sql($row);
   }
   sql_free($qh);

    // In $current, we have a reference on the element we are inserting
   if ($current->assess != $a) $current->set_assess_changed(true);
    $current->assess = $a;
   $current->set_inferred(false);
   }

  }

  function update_database($stop_on_error = true) {
      sql_query("START TRANSACTION");
       if (!$this->data->update_database($stop_on_error)) {
         if ($stop_on_error)  sql_query("ROLLBACK");
         return false;
      }
      sql_query("COMMIT");
//       sql_query("ROLLBACK");
      return true;

  }

  function update_masks() {
?>
<script type='text/javascript'>
var d= window.parent.document; //opener.document;
<?
     $this->data->update_masks();
   print "\n</script>\n";

  }

  function print_changes() {
     $this->data->print_changes();
  }

  function reset() {
     $this->data->reset();
  }

  function inference() {
    $this->data->reset();
    $this->data->inference();
    $this->data->final_inference();
  }



  var $false_value = false; // always false (for references)

  /** Get an assessment : returns an array
    0: assessment, 1: in pool (yes/no) ? 2: to assess ? 3: inferred (yes/no) ?
    4: e-min, 5: e-max, 6: s-min, 7: s-max

  */
  function &get_element($xpath) {
//     print "Getting assessment for $xpath<br>";
    $current = &$this->data;
    preg_match_all('-/([\-\w]+)\[(\d+)\]-',$xpath,$m, PREG_SET_ORDER);
    for($i = 0; $i < sizeof($m); $i++) {
      for($j = 0; $j < sizeof($current->children); $j++) {
        if ($current->children[$j]->tag == $m[$i][1] && $current->children[$j]->rank == $m[$i][2]) {
            $current = &$current->children[$j];
            continue 2;
        }
      }
      return $this->false_value;
    }

    return $current;
  }

  function &get_element_by_id($xid) {
    $current = &$this->data;
//     print "Searching $xid: ";
   while ($current && (sizeof($current->children) > 0) && ($current->xid < $xid) && ($current->post >= $xid)) {
//     print "[" . $current->xid  . "," . $current->post . "," . sizeof($current->children) . "]";
     for($j = 0; $j < sizeof($current->children); $j++) {
       $child = &$current->children[$j];
//       print "($j: " . $child->xid  . "," . $child->post . ")";
       if ($child->xid == $xid) return $child;
       if ($child->post >= $xid) { $current = &$child; continue 2; }
    }
    break;
   }
    if ($current->xid == $xid) return $current;
    return false;
  }
   
} // [end of class] Assessments




function get_stats_string(&$stats, $all = true, $goto = false) {
   global $sorted_assessments, $assessments;
foreach($sorted_assessments as $a) {
   
   if ($goto) {
      $xs = " onclick=\"show_goto_panel('" . ($a == "U" ? "A" : "$a") . "',event)\" ";
   }
   if ($goto) $s .= '<span><div class="help_bottom">To the right of this icon is the number of elements which have been assessed <em>' . $assessments[$a] . '</em>. If you click on this icon, you can then navigate through these elements</div>';
   if ($all || $stats[$a] > 0) $s .=  get_evaluation_img($a,false,false,true,false,$xs) 
            . "<span id='S_$a'> " .  intval($stats[$a]) . " </span> ";
   if ($goto) $s .= '</span>';
   if ($a == 'U' && ($all || $stats["I"] > 0)) { 
      if ($goto) $s .='<span><div class="help_bottom">To the right of this icon is the number of elements which are <em>non consistent</em> according to the active rules. If you click on this icon, you can then navigate through these elements</div>';
      $s .= get_evaluation_img("I",false,false,false,true,$goto ? " onclick='show_goto_panel(\"I\",event)'" : "") . " <span id='S_I'>" . intval($stats["I"]) . " </span> ";
      if ($goto) $s .= '</span>';
   }
}
return $s;
}

function print_js_stats_string($document,&$stats, $all = true) {
  global $sorted_assessments;
  foreach($sorted_assessments as $a) {
    ?>
      <?=$document?>.set_stat("<?=$a?>",<?=intval($stats[$a])?>);
    <?
  }
}

?>
