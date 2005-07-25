// Javascript code
// Article view javascript code
// (c) B. Piwowarski, 2004-2005


// ==================
// ================== Misc
// ==================


// *** Display a message box for a short amount of time
var message_id = 0;
function display_message(type,msg) {
   var div = document.createElement("div");
   div.appendChild(document.createTextNode(msg));
   div.setAttribute("class","message_" + type);
   message_id++;
   div.id = "message_" + message_id;
   document.getElementById("body").appendChild(div);
   setTimeout('message_clear("' + div.id + '")',1200);

}
function message_clear(id) {
   var x = document.getElementById(id);
   if (x) x.parentNode.removeChild(x);
}



// ==================
// ================== Navigation in the XML document
// ==================

var count = 0;


function get_container(x) {
   while (x && x.namespaceURI != documentns) x = x.parentNode;
   return x;
}

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



function toggle_treeview() {
  right_panel("treeview","img_treeview",treeview_url);
}

// -*-
// -*- X-RAI navigation
// -*-

// Container elements have a class set to "xmle"
// These elements have the following attributes:
// 1. id = the id of the node in the XML collection
// 2. i:post = the id of the last descendant of the node (= to id if no descendants)
// 3. i:p = the parent id

var ELEMENT_NODE = 1;

var XRai = {
   // last mouse coordinates
   lastX : 0,
   lastY : 0,

   /*
      Returns true if x is in y
      @note assume that x and y are X-Rai elements
   */
   is_in: function(x, y) {
      for(x = x.parentNode; x; x = x.parentNode)
         if (x==y) return true;
      return false;
   },

   parent: function(e) {
      var x = e.parentNode;
      if (!x || x.namespaceURI != documentns) return null;
      return x;
   },

   nextSibling: function(e) {
      e = e.nextSibling;
      while (e && (e.nodeType != ELEMENT_NODE || e.namespaceURI != documentns)) e = e.nextSibling;
      return e;
   },

   firstChild: function(e) {
      e = e.firstChild;
      while (e && (e.nodeType != ELEMENT_NODE || e.namespaceURI != documentns)) e = e.nextSibling;
      return e;
   },

   previousSibling: function(e) {
      e = e.previousSibling;
      while (e && (e.nodeType != ELEMENT_NODE || e.namespaceURI != documentns)) e = e.previousSibling;
      return e;
   },


   /** Return the previous element (document order): previous sibling or parent */
   previous: function(x) {
      var y;
      if (y= XRai.previousSibling(x)) return y;
      return XRai.parentNode(x);
   },

   /** Return the next element: first child, next sibling or the first ancestor next sibling */
   next: function(x) {
      var y;
      if (y= XRai.firstChild(x)) return y;
      while (x != null) {
         if (y = XRai.nextSibling(x)) return y;
         x = XRai.parent(x);
      }
      return null;
   },

   /** Return the previous element (document order): previous sibling or parent */
   noDirectPrevious: function(x) {
      var y;
      if (y= XRai.previousSibling(x)) return y;
      while (x != null) {
         if (y = XRai.previousSibling(x)) return y;
         x = XRai.parent(x);
      }
      return null;
   },

   /** Return the next element: first child, next sibling or the first ancestor next sibling */
   noDirectNext: function(x) {
      var y;
      while (x != null) {
         if (y = XRai.nextSibling(x)) return y;
         x = XRai.parent(x);
      }
      return null;
   },

   /** Returns the next XML node leaf after x */
   nextLeaf: function(x) {
      var y = XRai.nextSibling(x);
      while (y == null && x != null) {
         x = XRai.parent(x);
         if (x) y = XRai.nextSibling(x);
      }
      if (y == null) return null;

      var z = y;
      while (z != null) { y = z; z = XRai.firstChild(y); }
      return y;
   },

   nextElementTo: function(x,y) {
      if (x == y) return null;
      var z = null;
      while (x && z == null) {
         z = XRai.nextSibling(x);
         if (z == null) x = XRai.parent(x);
         if (debug) window.dump("Current x is = " + XRai.getPath(x) + " / z is " + XRai.getPath(z) + "\n");
      }
      x = z;
      while (x != null && XRai.is_in(y,x)) {
         if (debug) window.dump("  Loop " + XRai.getPath(y) + " is in " + XRai.getPath(x) + "\n");
         x = XRai.firstChild(x);
      }
      return x;
   },


   getPath: function(e) {
      if (e == null) return null;
      if (e.namespaceURI != documentns) { alert("Can't get the path of " + e + ": element " + e.tagName + " NS is " + e.namespaceURI); return; }
      var s = "";
      var f;
      do {
         f = XRai.parent(e);
         var n = 1;
         if (f) for(var x = XRai.firstChild(f); x !=  e; x = XRai.nextSibling(x)) {
            if (x.tagName == e.tagName) n++;
         }
         s = "/" + e.tagName + "[" + n + "]" + s;
      } while (e = f);
      return s;
   },

// Return the element "path" or null
   resolveXPath: function(path) {
      alert("Not implemented");
      var path_array = path.match(/\w+\[\d+\]+/g);
      var x = document.getElementById(root_xid);
      if (!path_array || x.getAttribute("path") != path_array[0]) return false;
      for (var i = 1;  x && i < path_array.length; i++) {
         x = XRai.firstChild(x);
         while (x && x.getAttribute("path") != path_array[i]) x = XRai.nextSibling(x);
      }
      return x;
   },

   /** Handlers */
   mousemoved: function(event) {
//       window.dump("Mouse is: " + event.pageX + " / " + event.pageY + "\n");
     XRai.lastX = event.pageX;
     XRai.lastY = event.pageY;
   },

   mouseover: function(event) {
     if (event.target.tagName == "a" && event.target.namespaceURI == xrains) {
      alert("coucou");
     }
   },
     
   mouseout: function(event) {
   }

}

// ==================
// ================== Events
// ==================

// -*- Context menu
var current_nav_element = false;

function nav_goto(event) {
  var x = false;
  switch(event.currentTarget.id) {
    case "nav_parent":
      x = XRai.parent(current_nav_element);
      break;
    case "nav_next":
      x = XRai.nextSibling(current_nav_element);
      break;
    case "nav_prec":
      x = get_xrai_previous_sibling(current_nav_element);
      break;
    case "nav_child":
      x = XRai.firstChild(current_nav_element);
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

  document.getElementById("nav_parent").style.visibility = XRai.parent(current_nav_element) ? "" : "hidden";
  document.getElementById("nav_next").style.visibility = XRai.nextSibling(current_nav_element) ? "" : "hidden";
  document.getElementById("nav_prec").style.visibility = get_xrai_previous_sibling(current_nav_element) ? "" : "hidden";
  document.getElementById("nav_bookmark").src = bookmark_exists(current_nav_element) ? "img/rm_bookmark.png" : "img/add_bookmark.png";
//   document.getElementById().style.visibility = XRai.firstChild(current_nav_element) ? "visible" : "hidden";


  // Move, set visible
  show_div_xy(event.pageX,event.pageY,"navigation");
  event.stopPropagation();
  return true;
}

// -*- Events



function article_keypress(event) {
  // NORMAL
  if (!event.shiftKey && !event.ctrlKey) {
    switch(event.which) {
      case 84 /* t */ : toggle_treeview(); return false;
      case 66 /* b */: toggle_bookmarks(); return false;
      case 97 /* a */: show_eval_selected(XRai.lastX, XRai.lastY); return false;
         //window.scrollX + window.innerWidth / 2, window.scrollY + window.innerHeight / 2); return false;
      case 73: right_panel('informations','img_informations',base_url + '/iframe/informations.php'); return false;
      case 83: save_assessments(); return false;
    }
  }

  // SHIFT + CTRL
  else if (event.shiftKey && event.ctrlKey) {
     switch(event.which) {
        case 71: clear_selected(); return false;
     }
  }

  // CTRL
  else if (!event.shiftKey && event.ctrlKey) {
     switch(event.keyCode) {
        case 38: goUp(); return false;
     }
  }
  
   if (debug) window.dump("Key pressed: charchode" + event.charCode
        + ", keycode=" + event.keyCode
      + ", which=" + event.which + ", shiftKey=" + event.shiftKey + ", ctrlKey=" + event.ctrlKey
      + ", x= " + event.pageX + "\n");
  return collection_keypress(event);
}




// Handles a click
function do_click(e) {
   if (e.target.namespaceURI == xrains) {
      switch(e.target.tagName) {
         case "a": reassess(e); break;
      }
  }

  // Handle ctrl key + click in a passage to go down
  if (e.ctrlKey) {
    for(var x = e.target; x != null; x = x.parentNode) {
      if (x.getAttribute && x.getAttribute("name") == "relevant" && x.reference) {
         currentAssessed = x.reference;
         goDown();
         return;
      }
    }
  }

  e.stopPropagation(); // Not not bubble anymore
}

function do_dblclick(e) {
  var x = get_xml(e.target);
  if (!x) return;
  e.stopPropagation();
 if (e.ctrlKey || e.metaKey) {
   x = get_xmle(x); if (!x) return;
   var to_add = toggle_selection(x);
   var y = XRai.parent(x);
   if (!y) return; // no parent
   y = XRai.firstChild(y);
   var s = x.getAttribute("name").length;
   if (!y) { alert("No first child for our parent !?!"); return; }
   while (y) {
      if (x!= y && (y.hasAttribute("sel") ^ to_add))
      if (y.getAttribute("name").length == s) {// Not assessed
          toggle_selection(y);
      }
      y = XRai.nextSibling(y);
   }
  }
}

// ==================
// ================== Bookmarks
// ==================

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
   var path = XRai.getPath(e);
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
}




// ==================
// ================== Assessments
// ==================


// Sorted array containing XRange objects
var assessed_ranges = Array();
// Associative array containing
var assessed_ancestors = Array();

// Contains the reference to the element being re-assessed
var currentAssessed = null;

// Show the eval panel

function show_eval_panel(px,py) {
  document.getElementById("eval_breakup_link").style.display = currentAssessed ? "block" : "none";
  var eval = document.getElementById("eval_div");

   // Check for valid assessments
   // ie a >= max(children) && <= any ancestor
   var max=3; var min = 0;

  show_div_xy(px,py,   "eval_div");
  return true;

}


function showEval(event,link) {
 // Get the panel element
 var x = get_xml(link);
  show_eval_panel(event.pageX,event.pageY,[get_xmle(x).id]);
  return true;
}


/** Selects the X-Rai elements which have been highlighted
   @param range A Range object
*/
function highlight(range) {
   // Find the beginning & end elements xids
   var x = get_container(range.startContainer);
   var y = get_container(range.endContainer);
   if (debug) window.dump(XRai.getPath(x) + " -> " + XRai.getPath(y) + "\n");

   var z;
//    if (z = XRai.previous(y)) {
//    } else if (z = XRai.next(x)) {
//    }

   var flag = true;
   for(; x != null; x = XRai.nextElementTo(x,y)) {
      window.dump("HIGH " + XRai.getPath(x) + "\n");
      if (x.getAttribute("name") != null && x.getAttribute("name") != "") {
         display_message("warning","The passage overlaps with an already assessed one (" + x.getAttribute("name") + ")");
         clear_selected();
         return false;
      }
      x.setAttribute("name","sel");
   }
   return true;
}

function clear_selected(event) {
  var selected = document.getElementsByName("sel");
  for(var i = 0; i < selected.length; i++) {
   selected[i].setAttribute("name",null);
  }
}

/** Assess the current selection */
function show_eval_selected(x,y) {
 var selection = window.getSelection();
 var range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
  if (range == null || range.collapsed) {
     display_message("warning","No selected elements");
     return;
  }

  clear_selected();
  if (highlight(range)) {
   selection.collapseToStart();
   show_eval_panel(x,y);
  }
 return true;
}

/** Re-assess */
function reassess(event) {
 // Display show eval panel
  event.target.xraiParent = currentAssessed;
  currentAssessed = event.target;
  show_eval_panel(event.pageX, event.pageY);
}


// Structure
// currentAssessed is a <xrai:a> element if we are already "zooming in" or null if top level

// <xrai:a>.lastElement is the last element of the highlighted passage
// <xrai:a>.parentPassage is the containing passage (if there is any)
// <xrai.a>.nextPassage is the next passage at this level
// <xrai:a>.firstSubpassage is the first sub-passage



function setPassage(p,v) {
   if (!v) p.setAttribute("hidden",""); else p.removeAttribute("hidden");
   for(var x = p.parentNode; x != null; x = XRai.nextElementTo(x,p.lastElement)) {
      if (v) x.reference = p;
      x.setAttribute("name",v ? "relevant" : null);
   }
}

function changesSubpassages(p,v) {
   for(var p = currentAssessed.firstSubpassage; p != null; p = p.nextPassage)
      setPassage(p,v);
}

/** Hide surroundings

 */
function hideSurroundings(element, flag) {
      var y = element.parentNode;
      while (y = XRai.noDirectPrevious(y)) {
         if (flag) y.setAttribute("hidden","true");
         else y.removeAttribute("hidden");
      }

      y = element.lastElement;
      while (y = XRai.noDirectNext(y))
         if (flag) y.setAttribute("hidden","true");
         else y.removeAttribute("hidden");
}


// Update passage information
function updatePassageInfo() {
   var info = document.getElementById("assessedPassageSpan");
   if (currentAssessed) {
      for(var x = info.firstChild; x!=null; x=x.nextSibling) {
         if (x && x.tagName == "xrai:a") {
            x.setAttribute("a",currentAssessed.getAttribute("a"));
            break;
         }
      }
      info.style.display = null;
   } else {
      info.style.display = "none";
   }
}

// Function called when the user wants to go "up"
// If innermost, go to the "up" URL
// Otherwise, go up using currentAssessed.parent
function goUp() {
   if (currentAssessed) {
      // Deselect current level highlighted passages
      changesSubpassages(currentAssessed,false);
      var p = currentAssessed;
      currentAssessed = currentAssessed.parentPassage;
      if (currentAssessed) changesSubpassages(currentAssessed,true);
      else setPassage(p,true);

      // Unhide components: to optimize
      hideSurroundings(p,false);
      if (currentAssessed) hideSurroundings(currentAssessed,true);
      scrollTo(0,p.scrollY);
  }
  
  updatePassageInfo();
}

function goDown() {
   // Save scroll position
   currentAssessed.scrollY = scrollY;

   var last = currentAssessed.lastElement;
   // Hide other components
   hideSurroundings(currentAssessed,true);

   // * Restablish the highlighting at this level

   // (a) Null the current highlighting of the passage
   currentAssessed.setAttribute("hidden","");
   var first = XRai.nextSibling(currentAssessed);
   for(var x = currentAssessed.parentNode; x != null; x = XRai.nextElementTo(x,last))
      x.setAttribute("name",null);

   // (b) highlight subpassages 
   changesSubpassages(currentAssessed,true);

   updatePassageInfo();
   scrollTo(0,0);
}

/** Assess the current selection */
function assess(e,a,the_event) {
   var ts = get_time_string();

  window.dump("Assessing with " + a + "\n");

  // Otherwise
  var selected = document.getElementsByName("sel");
  if (selected.length > 0) {
     changed = true;
     if (a == "0") {
         // The passage was not assessed
        for(var i = 0; i < selected.length; i++) {
            selected[i].setAttribute("name", null);
        }
     } else {
        // The passage was assessed

        // Create an xrai tag and add it to the currentPassage children
        var xraia = document.createElementNS(xrains,"a");
        if (currentAssessed) {
         xraia.nextPassage = currentAssessed.firstSubpassage;
         xraia.parentPassage = currentAssessed;
         currentAssessed.firstSubpassage = xraia;
        }
        xraia.setAttribute("a",a);

        // Insert <xrai:a> just before the first child of the first selected element
        xraia.lastElement = selected[selected.length-1];
        selected[0].insertBefore(xraia,selected[0].firstChild);

        setPassage(xraia,true);


     }
  }

  var save = document.getElementById("save");
  if (changed) { save.src = baseurl + "img/filesave.png"; save.setAttribute("title",changed.length + " assessment(s) to save");  }
  else { save.src =  baseurl + "img/filenosave.png"; save.setAttribute("title","No assessment to save"); }
}
/*
   Ordered array of elements

   */

// Add/remove an id to the array
function toggle_from_array(a,x) {
  var i = 0;
  while (i < a.length && a[i] < x) i++;
  if (a[i] == x)  { a.splice(i,1);  return false; }
  else a.splice(i,0,x);
  return true;
}

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



// *************
// * Inference *
// *************




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

var changed = false;



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





// **** Check the validity of the document ****

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

