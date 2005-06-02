// Javascript code
// Article view javascript code
// (c) B. Piwowarski, 2004


var count = 0;

function get_xml(x) {
  if (x.className == "xml") return x;
  x = x.parentNode; if (x.className == "xml") return x;
  return false;  
}


function get_first_xml(x) {
	while (x && x.className != "xml") x = x.firstChild;
	return x;
}

function get_xmle(x) {
  while (x && x.className != "xmle") x = x.parentNode;
  return x;
}

var boxed = false; // old boxed
var boxed_border = "";
function inex_mousemoved(from,to) {
  var old_to = to;
  to = get_xml(to);
  if (to = get_xmle(to)) {
    if (boxed == to) return;
    if (boxed) boxed.style.border = "";
    boxed = to;
    boxed_border = boxed.style.border;
    boxed.style.border = "1px solid black";
    old_to.setAttribute("title",get_xrai_path(boxed));
    return;
  } else {
    if (boxed) {
    old_to.removeAttribute("title");
    boxed.style.border = "";
    boxed = false;
  }
 }
}

function inex_mouseover(event) {
  inex_mousemoved(event.relatedTarget, event.target);
}


function toggle_treeview() {
  right_panel("treeview","img_treeview",treeview_url);
}

// -*-
// -*- X-RAI navigation
// -*-

function get_xrai_parent(e) {
  return document.getElementById(e.getAttribute("i:p"));
}

function get_xrai_next_sibling(e) {
  var xid = e.getAttribute("i:post");
  if (!xid) return;
  var x = document.getElementById(parseInt(xid)+1);
  if (!x) return;
  if (x.getAttribute("i:p") == e.getAttribute("i:p")) return x;
  return false;
}

function get_xrai_first_child(e) {
  if (!e) return false;
  var y = document.getElementById(parseInt(e.id) + 1);
  if (y && y.getAttribute("i:p") == e.id) return y;
  return false;
}

function get_xrai_previous_sibling(e) {
  if (e.id == 1) return false;
  var x = get_xrai_first_child(get_xrai_parent(e));
  var y = null;
  while (x && x != e) {
    y = x;
    x = get_xrai_next_sibling(x);
  }
  if (x==e) return y;  
  return false;
}

function get_xrai_path(e) {
  if (e.className != "xmle") { alert("Can't get the path: the element class is not an 'xmle'"); return; }
  var s = "";
  do {
    s = "/" + e.getAttribute("path") + s;
  } while (e = get_xrai_parent(e));  
  return s;
}

// Return the element "path" or null
function get_xrai_element_by_path(path) {
  var path_array = path.match(/\w+\[\d+\]+/g);
  var x = document.getElementById(root_xid);
  if (!path_array || x.getAttribute("path") != path_array[0]) return false;
  for (var i = 1;  x && i < path_array.length; i++) {
    x = get_xrai_first_child(x);
    while (x && x.getAttribute("path") != path_array[i]) x = get_xrai_next_sibling(x);
  }
  return x;
}

// -*- Context menu
var current_nav_element = false;

function nav_goto(event) {
  var x = false;
  switch(event.currentTarget.id) {
    case "nav_parent": 
      x = get_xrai_parent(current_nav_element);
      break;
    case "nav_next":
      x = get_xrai_next_sibling(current_nav_element);
      break;
    case "nav_prec":
      x = get_xrai_previous_sibling(current_nav_element);
      break;
    case "nav_child":
      x = get_xrai_first_child(current_nav_element);
      break;    
    case "nav_bookmark":
      add_bookmark(current_nav_element);
      break;
    default:
          alert("Bug? '" + event.currentTarget.id + "' is an unknown action");
  }
  if (x) show_focus(get_first_xml(x));
}

function show_nav(event) {

  
  var target = get_xml(event.target); 
  if (!target || !(target = get_xmle(target))) {
	 return; // not an xml tag
  }
  event.stopPropagation(); // Not not bubble anymore
  var nav = document.getElementById("navigation");
  // Disable / enable images
  current_nav_element = target;
  
  document.getElementById("nav_parent").style.visibility = get_xrai_parent(current_nav_element) ? "" : "hidden";
  document.getElementById("nav_next").style.visibility = get_xrai_next_sibling(current_nav_element) ? "" : "hidden";
  document.getElementById("nav_prec").style.visibility = get_xrai_previous_sibling(current_nav_element) ? "" : "hidden";
  document.getElementById("nav_bookmark").src = bookmark_exists(current_nav_element) ? "img/rm_bookmark.png" : "img/add_bookmark.png";
//   document.getElementById().style.visibility = get_xrai_first_child(current_nav_element) ? "visible" : "hidden";

  
  // Move, set visible
  show_div_xy(event.pageX,event.pageY,"navigation");
  event.stopPropagation();
  return true;
}

// -*- Events



function article_keypress(event) {
  if (event.shiftKey && !event.ctrlKey) {
    switch(event.which) {
      case 84: toggle_treeview(); return false;
      case 66: toggle_bookmarks(); return false;
      case 71:  show_eval_selected(window.scrollX + window.innerWidth / 2, window.scrollY + window.innerHeight / 2); return false;
      case 73: right_panel('informations','img_informations',base_url + '/iframe/informations.php'); return false;
      case 83: save_assessments(); return false;
    } 
  } else if (event.shiftKey && event.ctrlKey) {
     switch(event.which) {
        case 71: clear_selected(); return false; 
     }
  }
/*   alert("Key pressed: charchode" + event.charCode 
        + ", keycode=" + event.keyCode
      + ", which=" + event.which + ", shiftKey=" + event.shiftKey + ", ctrlKey=" + event.ctrlKey);*/
  return collection_keypress(event);
}

// -*- Bookmarks 

function bookmark_exists(e) {
  var doc = document.getElementById("bookmarks").contentDocument;
  return doc.getElementById(e.id);
}

function goto_path(event) {
   show_focus(get_first_xml(document.getElementById(event.target.id)));
  toggle_right_panel(document.getElementById("bookmarks"),document.getElementById("img_bookmarks"));
}

function add_bookmark(e) {
  var doc = document.getElementById("bookmarks").contentDocument;
  if (!doc) alert("Can't find the bookmarks document");
  var x = doc.getElementById(e.id);
  if (x) { 
   x.parentNode.removeChild(x);
      e.removeAttribute("selected");
   return; 
  }
  var list = doc.getElementById("list");
  var y = null;
  // Search a place for us
  var s="";
  var x = null;
  var id = parseInt(e.id);
  for(x = list.firstChild; x && (parseInt(x.id) < id); x = x.nextSibling) { s += x.id + " ";}
  // Construct the element
	var new_div = document.createElement("div");
   new_div.setAttribute("id",e.id);
   var path = get_xrai_path(e);
   new_div.onclick = goto_path;  
   if (path.length > 20) {
     new_div.setAttribute("title",path);
     path = "..." + path.substr(path.length-20);
   }
	new_div.appendChild(document.createTextNode(path));
   
   if (!x) list.appendChild(new_div);
   else list.insertBefore(new_div,x);
   
   e.setAttribute("selected","yes");
}
function toggle_bookmarks() {
  right_panel("bookmarks","img_bookmarks",null);
/*  var b_iframe = document.getElementById("bookmarks");
 var menubar = document.getElementById("menubar");
  b_iframe.style.top = menubar.scrollHeight + "px";
  toggle_right_panel(b_iframe,document.getElementById("img_bookmarks"));*/
}


/* List manager */



// Add/remove an id to the array
function toggle_from_array(a,x) {
  var i = 0;
  while (i < a.length && a[i] < x) i++;
  if (a[i] == x)  { a.splice(i,1);  return false; }
  else a.splice(i,0,x);
  return true;
}



/* 
      Evaluation
      
*/

var eval_elements = null;

// Show the eval panel : event (positioning 
function show_eval_panel(px,py, ids) {
  construct_assessment_tree(); // Check everything is OK
  
  for(var i = 0; i < ids.length; i++) {
  	var x = document.getElementById(ids[i]);
   add_to_tree(x);
   x.setAttribute("current","yes");
  }
  if (!tree_root) { alert("No tree root defined!"); return; }
  inference(tree_root,true); // Make some inference
  
  var eval = document.getElementById("eval_div");
  eval_elements = []; // Elements to be evaluated
  var mask = ~0;
  var new_div = document.createElement("div");
  var selected = null;
  for(var i = 0; i < ids.length; i++) {
  	var x = document.getElementById(ids[i]);
//    if (!x.parent) alert("No parent!?!");
   x.removeAttribute("current");
   eval_elements.push(x);
   var a = x.getAttribute("name");
   if (a == null || x.hasAttribute("ii")) a = "U";
   else if (a=="A") a = "U";
 
   if (!selected) selected = a;
   else if (selected != a) selected = "?";
	var path = get_xrai_path(x);
        var div_a = document.createElement("div");
        var node = document.createElement("span"); node.setAttribute("style","color: blue");
//         node.setAttribute("href","javascript:show_element(\"" + path + "\")");
         node.appendChild(document.createTextNode(path));
        div_a.appendChild(node);
        div_a.appendChild(document.createTextNode(" (" + assessments[a] + ")"));
        if (!assessments[a]) alert(a + " is not a valid assessment");
        new_div.appendChild(div_a);
       if (x.mask == null) { alert("Error (no mask for element)"); }
   	mask &= x.mask;
   }
   var eval_path = document.getElementById("eval_path");
   eval_path.replaceChild(new_div,eval_path.firstChild);

    bit_mask = 1;
   for(i=0; i < sorted_assessments.length; i++) {
    var a = sorted_assessments[i];
    var id = "asssess_" + a;
    var e = document.getElementById(id);
    if (e == null) alert("Error: " + id + " is not a valid id in document (" + i + ")");
    if (bit_mask & mask) {
      if (selected != a) { e.className = '';}
      else {  e.className = 'selected'; }
    } else {
      e.className = selected == a ? 'inconsistant' : 'disabled' ;
    }
    bit_mask <<= 1;
   }

   show_div_xy(px,py,   "eval_div");
   return true;

}

var selected_elements = new Array();

function showEval(event,link) {
 // Get the panel element
 var x = get_xml(link);
  show_eval_panel(event.pageX,event.pageY,[get_xmle(x).id]);
  return true;
}

function cb_hide_element(id) {
   var x = document.getElementById(id);
   if (x) x.style.visibility = "hidden"; 
}

function show_eval_selected(x,y) {
  if (selected_elements.length == 0) { 
     var x = document.getElementById("alert_no_selected");
     if (!x) alert("No selected elements"); 
     else {
        x.style.visibility = "visible";
        setTimeout('cb_hide_element("alert_no_selected")',1200);
     }
     return;
  }
  show_eval_panel(x,y,selected_elements);
 return true;
}


// Handles a click on an element tag


function toggle_selection(x) {
  var added = toggle_from_array(selected_elements, parseInt(x.id));
//   alert(get_xrai_path(x) + ": " + added);
  if (added) x.setAttribute("sel","");
  else x.removeAttribute("sel");
 return added;
}

function do_click(e) {
  var x = get_xml(e.target);
  if (!x) {
  	return; // not an xml tag
  }
  e.stopPropagation(); // Not not bubble anymore
  
  if (e.ctrlKey || e.altKey) { toggle_selection(get_xmle(x)); }
  else if (write_access) showEval(e,x);
}

function do_dblclick(e) {
  var x = get_xml(e.target);
  if (!x) return;
  e.stopPropagation();
 if (e.ctrlKey || e.metaKey) { 
   x = get_xmle(x); if (!x) return;
   var to_add = toggle_selection(x);
   var y = get_xrai_parent(x);
   if (!y) return; // no parent
   y = get_xrai_first_child(y);
   var s = x.getAttribute("name").length;
   if (!y) { alert("No first child for our parent !?!"); return; }
   while (y) {
      if (x!= y && (y.hasAttribute("sel") ^ to_add))
      if (y.getAttribute("name").length == s) {// Not assessed 
          toggle_selection(y);
      }
      y = get_xrai_next_sibling(y);
   }
  } 
}

function clear_selected(event) {
  for(var i = 0; i < selected_elements.length; i++) {
  	var x = document.getElementById(selected_elements[i]);
	if (!x)  continue; 
       x.removeAttribute("sel");
  }
  selected_elements = [];
}



// *************
// * Inference *
// *************

// Add an id to the array
function add_element_to_array(a,x) {
  var id = parseInt(x.id);
  var i = 0;
  while (i < a.length && parseInt(a[i].id) < id) i++;
  if (a[i] != x) a.splice(i,0,x);
}

// Remove an id from the array
function remove_element_from_array(a,x) {
  var id = parseInt(x.id);
  var i = 0;
  while (i < a.length && parseInt(a[i].id) < id) i++;
  if (a[i] = x) a.splice(i,1);
}


// 11 values (unknown, 0, 11, 12, 13, 21, 22, 23, 31, 32, 33) => 2^10 - 1 = 1023
// unknown: 1
// (0,0): 2
// (e,s) != (0,0) => 2^((e-1)*3+(s-1)+2)
var nomask = 2047;
var emask = { min: [ 2047, 2045, 2017, 1793], max: [3, 31, 255, 2047 ], val: [ 2, 28, 224, 1792]};
var smask = { min: [ 2047, 2045, 1753, 1169], max: [3, 295, 879, 2047], val: [ 2, 292, 584, 1168]};

function e_range(mask) {
  r = [ 3, 0 ];
  var i = 0;
  while (((emask.val[i] & mask) == 0) && i <= 3) { i++; } r[0] = i;
  while ((emask.val[i] & mask) && i <= 3) { r[1] = i; i++; }
  return r;
}
function s_range(mask) {
  r = [ 3, 0 ];
  var i = 0;
  while (((smask.val[i] & mask) == 0) && i <= 3) i++; r[0] = i;
  while ((smask.val[i] & mask) && i <= 3) { r[1] = i; i++; }
  return r;
}

function is_assessed(x) {
  return  x.getAttribute('name').length == 2 && !x.hasAttribute("current") && !x.hasAttribute("ii");
}

function get_exh(x) {
  var s  = x.getAttribute('name');
  if (s.length == 2 && !x.hasAttribute("current") && !x.hasAttribute("ii")) { var e = parseInt(s.substr(0,1)); return [e,e]; }
  return e_range(x.mask);
}
function get_spe(x) {
  var s  = x.getAttribute('name');
  if (s.length == 2 && !x.hasAttribute("current") && !x.hasAttribute("ii")) { var s = parseInt(s.substr(1,1)); return [s,s]; }
  return s_range(x.mask);
}

function get_ranges(x) {
  var s  = x.getAttribute('name');
  if (s.length == 2 && !x.hasAttribute("current") && !x.hasAttribute("ii")) { 
    var e = parseInt(s.substr(0,1)); 
    var s = parseInt(s.substr(1,1)); 
    return { e: [e,e], s: [s,s] };
  }
  return { e: e_range(x.mask), s: s_range(x.mask) };
}

function to_id_string(a) {
  var s ="";
  for(var i = 0; i < a.length; i++) s += " " + a[i].id;
  return s;
}



// -*- Inference
function inference(x,first_pass) {
  if (first_pass) x.mask = nomask;
  var omask = x.mask;
  var mask;
  var p;
  var tc = parseInt(x.getAttribute("nc")); // Number of children (inc. text nodes)
  do {
    mask = x.mask;

    if (p = x.parent) {
      var pr = get_ranges(p);
      x.mask &= emask.max[pr.e[1]]; // Rule 1.1
//       if (pr.s[0] == 3 && pr.e[1] > 1 && tc > 1) x.mask &= emask.max[pr.e[1]-1]; // Rule 6.1
    }
    
    if (x.children) {
      var smin = 3; var smax = 0;
      var emin_max = 0; // Max of min(e) 
      var s_inf_child = x, s_sup_child = x; // Only s-inf (sup) child
      var esum_min = 0, esum_max = 0;
      
      // Process children (first pass)
      for(var i = 0; i < x.children.length; i++) {
        var child = x.children[i];
        if (first_pass || mask != x.mask) inference(child,first_pass);
        var r = get_ranges(child);
        x.mask &= emask.min[r.e[0]]; // Rule 1.1
        // Update statistics
        esum_min += r.e[0]; 
        esum_max += r.e[1];
        if (smin > r.s[0]) smin = r.s[0];
        if (smax < r.s[1]) smax = r.s[1];
        if (emin_max < r.e[0]) emin_max = r.e[0];
        if (s_inf_child && r.s[0] <= smax) if (s_inf_child == x) s_inf_child = child; else s_inf_child = null;
        if (s_sup_child && r.s[1] >= smin) if (s_sup_child == x) s_sup_child = child; else s_sup_child = null;
      }
      
/*      if (tc > 1) {
        if (emin_max >= 2) x.mask &= ~(emask.val[2] & smask.val[3]); // Rule 6.2
        if (emin_max >= 3) x.mask &= ~(emask.val[3] & smask.val[3]); // Rule 6.2
      }*/
      
      // Process children (second pass) : only if we have all children in the tree
      var c_changed = false;
      var r = get_ranges(x);
      if (tc == 0) {
        x.mask &= smask.max[0];
        x.mask &= emask.max[0];
      } else if (tc == x.children.length) {
        x.mask &= smask.max[smax]; // Rule 3.1
        x.mask &= smask.min[smin]; // Rule 5.1
        if (esum_max < 3)  x.mask &= emask.max[esum_max];  // Rule 4.1
        var c_emin = get_exh(x)[0] - esum_max;
//         alert("esum_min = " + esum_min + " => c_emin=" + c_emin);
        for(var i = 0; i < x.children.length; i++) {
          var child = x.children[i];
          var cmask = child.mask;
          var e = c_emin + get_exh(child)[1];
          if (e <= 3 && e> 0) { 
            child.mask &= emask.min[e]; 
//              alert("Rule 4.2 for " + get_xrai_path(child) + " = " + c_emin + ", " + e + "," + esum_min); 
          }// Rule 4.2
          if (s_sup_child == child) { child.mask &= smask.min[r.s[0]]; /*alert(get_xrai_path(child) + " (1)");*/ }
          if (s_inf_child == child) { child.mask &= smask.max[r.s[1]]; /*alert(get_xrai_path(child) + " (2)");*/ }
          if (cmask != child.mask) inference(child,false);
        } 
      } // Has all children ?
    } // Has one child at least
    first_pass = false;
  } while (mask != x.mask);
  
//   alert(get_xrai_path(x) + " (" + x.id + ")" + " has now mask = " + x.mask + " and " + tc + " child(ren)");
  
  return x.mask != omask; // Have we changed?
}


// update the name (assessment), ii (infered), ic (inconsistant) flags
function update_view(x,needed) {
  if (x == tree_root) reset_statistics();
  var er = e_range(x.mask); var sr = s_range(x.mask);
  
  var s = x.getAttribute("name"); // The current assessment
  var old_name = s;
  var ii = null; 
  var ic = null; // not inconsistant
  
  if ((x.hasAttribute("ii") || (s.length != 2))) { 
    // Inferred or not assessed: check the infered status
    s = "U";
    if (er[0] == er[1] && sr[0] == sr[1]) {
      ii = "yes";
      s = er[0] + "" + sr[0];
    } 
    ic = null;
  } else { 
    // The element is assessed: check consistency
    var e = parseInt(s.substr(0,1)); sp = parseInt(s.substr(1,1));
    if (e < er[0] || e > er[1] || sp < sr[0] || sp > sr[1]) {
      ic = "yes";
//       dump(get_xrai_path(x) + " is ic: " + er + ", " + sr + " and " + e + "-" + sr+ "\n");
    }
  }
  

  // Do we need children (ie, s is assessed & highly specific & ...?)
  var need_children = !(s.length == 1 || s== "00" || s== "13");
  if (x.children && need_children) {
    var esum = 0;
    for(var i = 0; i < x.children.length; i++) {
      var sc = x.children[i].getAttribute("name"); 
      if (sc.length == 2) esum += parseInt(sc.substr(0,1));
    }
    if (esum >= parseInt(s.substr(0,1))) need_children = false;
  }
  
  
  // To assess & recursive
  var is_todo = needed;
//   if (needed) alert(get_xrai_path(x) + " is needed!");
  
  if (x.children) for(var i = 0; i < x.children.length; i++) {
    var todo = update_view(x.children[i],need_children);
     is_todo |= todo;
//      if (todo) alert(get_xrai_path(x.children[i]) + " returned todo");
   }
  
  var a = "";
  if (ic == "yes")  a = "I";
  else a = s;
  
   if (is_todo || x.hasAttribute("ip")) { 
     if (s=="U") { s = "A"; a="U"; } 
     else if (s=="A") a="U"; 
   }
   else { 
     if (s == "A") { 
       s = "U";
       a = null; 
     } 
   else if (s == "U") a = null; 
   }
  
   if (a) statistics[a]++;
   
   // Update if needed
   if (s != x.getAttribute("name")) x.setAttribute("name",s);
   if (ii != x.getAttribute("ii")) {
     if (ii == "yes") x.setAttribute("ii","yes"); else x.removeAttribute("ii");
   }
   if (ic != x.getAttribute("ic")) if (ic == "yes") { 
     x.setAttribute("ic","yes"); 
   } else x.removeAttribute("ic");
   
//    dump(get_xrai_path(x) + ": ic=" + ic + ", ii=" + ii + ", name=" + s + "\n");
   return (s.length == 1 && is_todo) || (s != "00" && s.length == 2) ;
}

// -*- Build the assessment tree
var p_assessments = ['A','00','11','12','13','21','22','23'];
var tree_root = null;



function check_children(x) {

  if (!x.children) {
    x.children = [];
  for(var y = get_xrai_first_child(x); y; y = get_xrai_next_sibling(y))  {
      add_element_to_array(x.children,y);
   if (!y.parent) y.parent = x;
  } 
  }
}

function add_to_tree(x) {
  if (x.parent && x.children) return; // It's OK.

  check_children(x);
  
  // Add ancestor until it is OK
  var y = x;
  while (x && !x.parent) {
    y = get_xrai_parent(x);
//     if (!y) alert(x.id);
    x.parent = y;
    if (y) check_children(y);
    x = y;  
   }
  // Add children
}

function construct_assessment_tree() {
  if (tree_root) return; // already constructed
  var n = 0;
  tree_root = document.getElementById(root_xid);
  for(var i = 0; i < p_assessments.length; i++) {
    var list = document.getElementsByName(p_assessments[i]);
    for(var j = 0; j < list.length; j++) {
      // For each element, build from element to top
      add_to_tree(list[j]);
    }
  }
}



// -*- Save assessments

var changed = [];
var statistics = {};
var stats_assessments = [ 'I', 'U', '00','11','12','13','21','22','23', '31', '32', '33'];

function article_beforeunload(event) {
  if (changed.length > 0) 
    return "X-Rai warning: " + (changed.length > 1 ? changed.length + " assessment are" : "1 assessment is") + " not saved.";
}


function reset_statistics() {
  for(var i = 0; i < stats_assessments.length; i++)
      statistics[stats_assessments[i]] = 0;
}

function update_stat_view() {
  for(var i = 0; i < stats_assessments.length; i++) {
        var x = document.getElementById("S_" + stats_assessments[i])
        if (x) x.firstChild.nodeValue = statistics[stats_assessments[i]];
        else alert("No S_" +stats_assessments[i]); 
      }
}

function set_stat(a,i) {
  document.getElementById("S_" + a).firstChild.nodeValue = i;
  statistics[a] = i;
//       <?=$document?>.getElementById("S_<?=$a?>").firstChild.nodeValue = "<?=intval($stats[$a])?>";
}

function saved() {
  for(var i = 0; i < changed.length; i++) {
    changed[i].removeAttribute("old");
  }
  changed = [];
   var save = document.getElementById("save");
   save.enabled = false;
   save.src =  baseurl + "img/filenosave.png"; 
   save.setAttribute("title","No assessment to save"); 
}


function lpad(c,s,l) {
   s = String(s);
   while (s.length < l)  s = c + s;
   return String(s);
}

function get_time_string() {
   var d = new Date();
   var s = String(d.getUTCFullYear() + "%2d" + lpad("0",d.getUTCMonth()+1,2) + "%2d" + lpad("0",d.getUTCDate(),2)); 
   s += "%20";
   s += lpad("0",d.getUTCHours(),2) + "%3a" + lpad("0",d.getUTCMinutes(),2)  + "%3a" + lpad("0",d.getUTCSeconds(),2);
//    alert (s);
   return s;
}

function assess(e,a,the_event) {
   var ts = get_time_string();
            
  if (e.className != "") return false;
  for(var i = 0; i < eval_elements.length; i++) {
    var x = eval_elements[i];
    var old = x.getAttribute("old");
    if (old && old == a) { 
      x.removeAttribute("old"); 
      remove_element_from_array(changed,x); 
    }
    else { 
      if (!old) x.setAttribute("old",x.getAttribute("name"));
      add_element_to_array(changed,x);
    }
    x.setAttribute("name",a);
    x.removeAttribute("ii");
    x.setAttribute("ts",ts);
  }
  var save = document.getElementById("save");
  if (changed.length > 0) { save.src = baseurl + "img/filesave.png"; save.setAttribute("title",changed.length + " assessment(s) to save");  }
  else { save.src =  baseurl + "img/filenosave.png"; save.setAttribute("title","No assessment to save"); }
  
  inference(tree_root,true); // update the view
  update_view(tree_root);
  update_stat_view();
}

function save_assessments() {
  if (changed.length == 0) return;
  var my_form = document.getElementById("form_save");
  var my_assess = document.getElementById("form_assessments");
  if (!my_assess || !my_form) { alert("Can't find the object needed to submit assessments"); return; }
  e = document.getElementById("assessing");
  if (!e) alert("Hmmm. Can't retrieve the iframe 'assessing' for assessing");
   e.contentDocument.documentElement.innerHTML = "<html><head><title>Waiting</title></head><body><div style='text-align: center; font-weight: bold;'>Connecting to the server...</div></body></html>";
  e.style.visibility = "visible";
  
  var paths_qs = "";
  for(var i = 0; i < changed.length; i++) {
    var a = changed[i].getAttribute("name");
    if (a=="A" || changed[i].getAttribute("ii") == "yes") a = "U";
    paths_qs += "&assess[" + changed[i].id + "]=" + a
          + "&ts[" + changed[i].id + "]=" + changed[i].getAttribute("ts");
  }
//   alert(paths_qs);
  my_assess.value = paths_qs;
//   e.src = assess_url + paths_qs  ;
//   alert("Submitting");
  my_form.submit();
}

// Todo
var current_todo = 0;
function check_todo(list,way) {
	if (!list || list.length == 0) {
		if (confirm("No more elements to assess in this view. Do you like to jump to the " + way + " view where there is an element to assess ?")) {
			window.location = "article.php?view_jump=1&id_pool=" + id_pool + "&next=" + (way == "next" ? "1" : "0") + "&view_xid=" + view_xid;
		}
		return false;
	}
   return false;
}

// *** Quick assessment navigation

var current_goto = "";
var current_nodes = {};

function show_goto_panel(x,event) {
   current_goto = x;
   show_div_xy(event.pageX,event.pageY,"s_nav");   
}
      
function goto_next_assessment() {
   var list = document.getElementsByName(current_goto);
   if (list.length == 0) return;
   if (current_nodes[current_goto] == null)
      current_nodes[current_goto] = 0;
   else 
      current_nodes[current_goto] = (current_nodes[current_goto] + 1) % list.length;
  show_focus(get_first_xml(list[current_nodes[current_goto]]));  
}

function goto_previous_assessment() {
   var list = document.getElementsByName(current_goto);
   if (list.length == 0) return;
   if (current_nodes[current_goto] == null)
         current_nodes[current_goto] = 0;
   else 
      current_nodes[current_goto] = (current_nodes[current_goto] + list.length - 1) % list.length;
   show_focus(get_first_xml(list[current_nodes[current_goto]]));  
}

function todo_next() {
  var list = document.getElementsByName("A");
  if (list.length == 0) return check_todo(list,"next");
  current_todo = (current_todo + 1) % list.length;
  show_focus(get_first_xml(list[current_todo]));  
}

function todo_previous() {
  var list = document.getElementsByName("A");
  if (list.length == 0) return check_todo(list,"previous");
  current_todo = (current_todo + list.length - 1) % list.length;
  show_focus(get_first_xml(list[current_todo]));
}



function check_valid_xml() {
   if (document.firstChild.nodeName == "parsererror") {
      if (!force_regeneration) {
//          alert(document.firstChild.textContent);
//          return;
         var r = confirm("X-Rai has detected that the (cached) XML was invalid. It will now try to re-generate the cached file; if this does not work, it will try to generate it again *without* including MathML formulas.");
         if (r) window.location = window.location + "&force=1";
         return;
      } else if (force_regeneration == 1) {
//          alert(document.firstChild.textContent);
//          return;
         var r = confirm("X-Rai has detected *again* that the XML was invalid. It will now try to re-generate the cached file without including MathML formulas; if this does not work, an email will be sent with the bug report.");
         if (r) window.location = window.location + "&force=2";
         return;
      } else if (force_regeneration == 3) {
         alert("Nothing is done (third level)");
         return;
      } else {
         var s;
         if (document.firstChild && document.firstChild.childNodes[0] && document.firstChild.childNodes[1].firstChild)
            s = document.firstChild.childNodes[0].nodeValue + "\n\n\n" + document.firstChild.childNodes[1].firstChild.nodeValue;
         else if (document.firstChild)
            s = document.firstChild.textContent;
         else 
            s = "???";
         var nexturl;
         if (window.id_pool)
               nexturl = "article.php?view_jump=1&amp;id_pool=" + id_pool + "&amp;view_xid=" + view_xid + "&amp;next=" ;
         // Generate the bug report
         window.location = base_url + "/bug_report.php?error=" + escape(s)
               + "&whattodo= " 
               + (window.id_pool ? escape('Now you can: <ul><li>go to the <a href="' + nexturl + '0">previous element to assess</a>,</li>'
               + '<li>or go to the <a href="' + nexturl + '1">next element to assess</a>,</li>'
               + '<li>or go to the <a href="' + base_url + '/pool?id_pool=' +  id_pool + '">pool summary</a>.</li></ul>') : escape('You can go back to the <a href="' + base_url + '">home page</a>'));
      }
      

      return;
   }
   if (!document_is_loaded) {
      setTimeout("check_valid_xml()",500);
      return;
   } else {
//       print_xml_positions_r(document.getElementById("inex"));
   }
   
}
setTimeout('check_valid_xml()',500);

// Print XML positions
function print_xml_positions_r(x) {
   if (x.firstChild) print_xml_positions_r(x.firstChild);
   if (x.nextSibling) print_xml_positions_r(x.nextSibling);
   if (x.nodeType != 1) return; // if x is not a XML element
   if (x.getAttribute("class") == "xmle") {
      window.dump("Found id " + x.id + " - " + x.offsetLeft + "-" + (x.offsetLeft + x.offsetWidth) + "," + x.offsetTop+ "-" + (x.offsetTop + x.offsetHeight) + "\n");
   }
}

