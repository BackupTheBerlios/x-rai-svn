// Javascript code
// Article view javascript code
// (c) B. Piwowarski, 2004-2005


// ==================
// ================== Misc
// ==================

var max_exhaustivity = 2;
var xpe = new XPathEvaluator();
var nsResolver = xpe.createNSResolver(document.documentElement);

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

// *** Evaluate an XPath against a given node and return an array of nodes
// From http://kb.mozillazine.org/XPath
function evaluateXPath(aNode, aExpr) {
  var xpe = new XPathEvaluator();
  var nsResolver = xpe.createNSResolver(aNode.ownerDocument == null ?
    aNode.documentElement : aNode.ownerDocument.documentElement);
  var result = xpe.evaluate(aExpr, aNode, nsResolver, 0, null);
  var found = [];
  while (res = result.iterateNext())
    found.push(res);
  return found;
}

// *** Remove an element in an array
// A: no duplicate in the array
// Returns true if the element was found
function removeFromArray(a,x) {
   for(var i = 0; i < a.length; i++) {
      if (a[i] == x) {
         a[i] = a[a.length-1];
         a.pop();
         return true;
      }
   }
   return false;
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
//   right_panel("treeview","img_treeview",treeview_url);
}

// -*-
// -*- X-RAI navigation
// -*-

// Container elements have a class set to "xmle"
// These elements have the following attributes:
// 1. id = the id of the node in the XML collection
// 2. i:post = the id of the last descendant of the node (= to id if no descendants)
// 3. i:p = the parent id

// An element node has the code 1 (at least in gecko...)

var XRai = {
   // last mouse coordinates
   lastX : 0,
   lastY : 0,

   /*
      Returns true if x is in y
   */
   is_in: function(x, y) {
      return x.compareDocumentPosition(y) & Node.DOCUMENT_POSITION_CONTAINS;
   },

   parent: function(e) {
      var x = e.parentNode;
      if (!x || x.namespaceURI != documentns) return null;
      return x;
   },

   nextSibling: function(e) {
      e = e.nextSibling;
      while (e && (e.nodeType != Node.ELEMENT_NODE || e.namespaceURI != documentns)) e = e.nextSibling;
      return e;
   },

   firstChild: function(e) {
      e = e.firstChild;
      while (e && (e.nodeType != Node.ELEMENT_NODE || e.namespaceURI != documentns)) e = e.nextSibling;
      return e;
   },

   previousSibling: function(e) {
      e = e.previousSibling;
      while (e && (e.nodeType != Node.ELEMENT_NODE || e.namespaceURI != documentns)) e = e.previousSibling;
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

   /** Return the previous element (document order): previous sibling or ancestor first previous sibling*/
   noDirectPrevious: function(x) {
      var y;
      if (y= XRai.previousSibling(x)) return y;
      while (x != null) {
         if (y = XRai.previousSibling(x)) return y;
         x = XRai.parent(x);
      }
      return null;
   },

   /** Return the no direct next element:  next sibling or the first ancestor next sibling */
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
      if (y == null) return XRai.noDirectNext(x);
      var z = null;
      if (!XRai.is_in(y,x)) {
         while (x && z == null) {
            z = XRai.nextSibling(x);
            if (z == null) x = XRai.parent(x);
            if (debug) window.dump("Current x is = " + XRai.getPath(x) + " / z is " + XRai.getPath(z) + "\n");
         } 
         x = z;
      }
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

   getPassagePaths: function(e) {
      return this.getPath(e.parentNode) + (e.lastElement ? " -> " + this.getPath(e.lastElement) : "");
   },

   getRoot : function() {
      var root;
      for(root = document.getElementById("inex").firstChild; root && root.nodeType != Node.ELEMENT_NODE; root = root.nextSibling) {}
      return root;
   },

   /** Handlers */
   mousemoved: function(event) {
//       window.dump("Mouse is: " + event.pageX + " / " + event.pageY + "\n");
     XRai.lastX = event.pageX;
     XRai.lastY = event.pageY;
   },

   mouseover: function(event) {
     if (event.target.tagName == xraiatag && event.target.namespaceURI == xrains) {
//       alert("coucou");
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
//       case 84 /* t */ : toggle_treeview(); return false;
//       case 66 /* b */: toggle_bookmarks(); return false;
      case 97 /* a */: show_eval_selected(XRai.lastX, XRai.lastY); return false;
         //window.scrollX + window.innerWidth / 2, window.scrollY + window.innerHeight / 2); return false;
      case 73: right_panel('informations','img_informations',base_url + '/iframe/informations.php'); return false;
    }
  }

  // SHIFT + CTRL
  else if (event.shiftKey && event.ctrlKey) {
     switch(event.which) {
        case 71: clear_selected(); return false;
     }
  }

  // SHIFT
  else if (event.shiftKey && !event.ctrlKey) {
      if (event.which == 83) { save_assessments(); return false; }
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
         case xraiatag: reassess(e); break;
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
// ================== Assess
// ==================

// Structure
// currentPassage is a <xrai:a> element if we are already "zooming in" or null if top level

// <xrai:a>.lastElement is the last element of the highlighted passage (or null if not a passage, ie a containing element)
// <xrai:a>.parentPassage is the containing passage (if there is any)
// <xrai.a>.nextPassage is the next passage at this level
// <xrai:a>.firstSubpassage is the first sub-passage

// The xrai:a parent is ALWAYS an XML node
// and may contain:
// - a "passages" array (list of relevant passages directly below this node)
// - cAssessment which is the xrai:a element for this container

// Elements to assess are of two types:
// (1) Elements with unknown assessment (a = "U")
var cardUnknownAssessment = 0;
// (2) Elements which are not too small and have not enough assessed subpassages to explain it (missing attribute)
var cardMissingAssessment = 0;

// Sorted array containing XRange objects
var assessed_ranges = Array();
// Associative array containing
var assessed_ancestors = Array();

// Contains the reference to the current displayed passage
var currentPassage = null;
// Contains the current assessed passage (or null if from selection)
var currentAssessed = null;
// True if the current assessed passage is too "small"
var nobelow=0

// Contains the list of assessments to be removed when saving
var passagesToRemove = new Array();

// Count the number of changes since last save
var changed = 0;

/** Get the maximum exhaustivity below */
function getMaxExh(x,skip) {
   // (1) Is the xrai:a element assessed ?
   if (!skip) {
      var a = x.getAttribute("a");
      if (a && a != "U") return a;
   }

   // (2) Is the element a passage?
   var max = 0;
   if (x.lastElement) {
      for(var c = x.firstSubpassage; c != null; c = c.nextPassage)
         max = Math.max(max,getMaxExh(c,false));
   } else {
      var a = x.parentNode.passages;
      for(var i = 0; i < a.length; i++)
         max = Math.max(max,getMaxExh(a[i],false));
   }
   return max;
}

/** Get the minimum exhaustivity below
   Return either the assessment of this xrai:a element
   or the sum of the mimimum exhaustivity of its descendants
*/
function getMinExhBelow(x,skip) {
   // (1) Is the xrai:a element assessed ?
   if (!skip) {
      var a = x.getAttribute("a");
      if (a && a != "U") return a;
   }

   // (2) Is the element a passage?
   var sum = 0;
   if (x.lastElement) {
      for(var c = x.firstSubpassage; c != null; c = c.nextPassage)
         sum += getMinExhBelow(c,false);
   } else {
      var a = x.parentNode.passages;
      for(var i = 0; i < a.length; i++)
         sum += getMinExhBelow(a[i],false);
   }
   return sum;
}

// Get relevant subpassages of container x
// and put them in array t
function getRelevantSubpassages(x, t) {
   for(var i = 0; i < x.passages.length; i++) {
      if (x.passages[i].lastElement) t.push(x.passages[i]);
      else getRelevantSubpassages(x.passages[i],t);
   }
}

/**
    Show the eval panel
*/
function show_eval_panel(px,py) {
  var eval = document.getElementById("eval_div");

   // Check for valid assessments
   // ie a >= max(children) && <= any ancestor
   var max=max_exhaustivity; var min = 0;
   if (currentAssessed) {
      if (currentAssessed.lastElement) {
         if (currentAssessed) min = getMaxExh(currentAssessed, true);
         if (currentPassage)
            for(var p = currentPassage; p != null; p = p.parentPassage) {
               var a = p.getAttribute("a");
               window.dump("Passage " + XRai.getPath(p.parentNode) + " has a=" + a  + "\n");
               if (a != "U" && parseInt(a) < max) max = parseInt(a);
            }
      } else {
         // The assessed element is a container
         min = getMaxExh(currentAssessed,true);
         // Get all subpassages
         var t = new Array();
         getRelevantSubpassages(currentAssessed.parentNode,t);
         t.sort(sortF);
         currentAssessed.parentNode.setAttribute("root",1);
         // Hide all intervals
         for(var i = 0; i <= t.length; i++) {
            var y = i == 0 ? XRai.getRoot() : t[i-1].parentNode;
            var to = i < t.length ? t[i].parentNode : null;
            window.dump(y + " -> " + to + "\n");
            while ((y = XRai.nextElementTo(y,to)) && (y != to)) {
               y.setAttribute("hidden","1");
            }
         }
//          alert(XRai.getPath(currentAssessed.parentNode) + " -> " + XRai.getPath(y) + " -> "+XRai.getPath(t[0].parentNode));
      }
   }
   window.dump("Assessement must be in [" + min + "," + max + "]\n");

   // Disable invalid assessements
   for(var i = 0; i <= 2; i++) {
      var x = document.getElementById("assess_" + i);
      x.className = (i >= min && i <= max ? null : "disabled");
   }

  var nb = document.getElementById("nobelow");
  nobelow = currentAssessed && currentAssessed.hasAttribute("nobelow") ? true : false;
  nb.className = min > 0 ? "disabled" : (nobelow ? "on" : null);
  document.getElementById("eval_breakup_link").className = currentAssessed && currentAssessed.hasAttribute("nobelow") ? null : "disabled";
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
   if (debug) {
      window.dump(XRai.getPath(x) + " -> " + XRai.getPath(y) + "\n");
      if (currentPassage)
         window.dump(XRai.getPath(currentPassage.parentNode) + " -> " + XRai.getPath(currentPassage.lastElement) + "\n");
   }
   

   if (currentPassage && currentPassage.parentNode == x && currentPassage.lastElement == y) {
      display_message("notice","The selected passage is too big");
      return;
   }

   // Check if a start/end ancestor is highlighted
   for(var k=0; k < max_exhaustivity; k++) {
      z = k == 0 ? x : y;
//          window.dump("Checking z=" + z  + "\n");
      do {
         if (z.getAttribute("name") != null && z.getAttribute("name") != "") {
            display_message("warning","The passage overlaps with an already assessed one");
            clear_selected();
            return false;
         }
      } while (z = XRai.parent(z));
   }
   
   // Highlight
   var flag = true;
   for(; x != null; x = XRai.nextElementTo(x,y)) {
      window.dump("HIGH " + XRai.getPath(x) + "\n");
      if ((x.getAttribute("name") != null && x.getAttribute("name") != "") || (x.passages && x.passages.length > 0)) {
         display_message("warning","The passage overlaps with an already assessed one");
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
 if (!id_pool) return;
 var selection = window.getSelection();
 var range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
  if (range == null || range.collapsed) {
     display_message("warning","No selected elements");
     return;
  }

  clear_selected();
  if (highlight(range)) {
   currentAssessed = null;
   selection.collapseToStart();
   show_eval_panel(x,y);
  }
 return true;
}

/** Re-assess */
function reassess(event) {
 // Display show eval panel
  currentAssessed = event.target;
  show_eval_panel(event.pageX, event.pageY);
}



function setPassage(p,v) {
   if (!v) p.setAttribute("hidden",""); else p.removeAttribute("hidden");
   for(var x = p.parentNode; x != null; x = XRai.nextElementTo(x,p.lastElement)) {
      if (v) x.reference = p;
      x.setAttribute("name",v ? "relevant" : null);
   }
}

function changesSubpassages(p,v) {
   for(var p = currentPassage.firstSubpassage; p != null; p = p.nextPassage)
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
   if (currentPassage) {
      for(var x = info.firstChild; x!=null; x=x.nextSibling) {
         if (x && x.tagName == "xrai:a") {
            x.setAttribute("a",currentPassage.getAttribute("a"));
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
// Otherwise, go up using currentPassage.parent
function goUp() {
   if (currentPassage) {
      // Deselect current level highlighted passages
      changesSubpassages(currentPassage,false);
      var p = currentPassage;
      currentPassage = currentPassage.parentPassage;
      if (currentPassage) changesSubpassages(currentPassage,true);
      else setPassage(p,true);

      // Unhide components: to optimize
      hideSurroundings(p,false);
      if (currentPassage) hideSurroundings(currentPassage,true);
      scrollTo(0,p.scrollY);

      if (!currentPassage) 
         for(var x = p.parentNode; x; x = XRai.parent(x))
            if (x.cAssessment) x.cAssessment.removeAttribute("hidden");
     updatePassageInfo();
  } else {
    if (up_url) window.location = up_url;
  }

}

function goDown() {
   // Check to see if we can go down
   if (currentAssessed.hasAttribute("nobelow")) {
      display_message("notice","The passage has been assessed as the smallest meaningfull unit");
      return;
   }
   if (currentAssessed.parentNode == currentAssessed.lastElement) {
      display_message("notice","There is no subpassage");
      return;
   }

   // If it is the first time we go down, turn off all ancestors
   if (!currentPassage) {
      for(var x = currentAssessed.parentNode; x; x = XRai.parent(x)) {
         if (x.cAssessment) x.cAssessment.setAttribute("hidden","1");
      }
   }

   // Sets the current passage
   currentPassage = currentAssessed;

   // Save scroll position
   currentPassage.scrollY = scrollY;

   var last = currentAssessed.lastElement;
   // Hide other components
   hideSurroundings(currentPassage,true);

   // * Restablish the highlighting at this level

   // (a) Null the current highlighting of the passage
   currentPassage.setAttribute("hidden","");
   var first = XRai.nextSibling(currentPassage);
   for(var x = currentPassage.parentNode; x != null; x = XRai.nextElementTo(x,last))
      x.setAttribute("name",null);

   // (b) highlight subpassages
   changesSubpassages(currentPassage,true);

   updatePassageInfo();
   scrollTo(0,0);
}



function updateContainers(element) {
   // (1) Select the lowest common ancestor
   var x = element.parentNode;
   while (x && !XRai.is_in(element.lastElement,x)) x = XRai.parent(x);

   // (2) Propagate
   var current = element;
   var toremove = null;
   window.dump("\n");

   while (x) {
      if (!x.passages) x.passages = new Array();
      else if (toremove) removeFromArray(x.passages,toremove);
      x.passages.push(current);
      if (debug) {
         window.dump("Element " + XRai.getPath(x) + " has [" + x.passages.length + "] ref. to (remove="
                +  (!toremove ? "null" : XRai.getPath(toremove.parentNode))
                + ", add=" + XRai.getPath(current.parentNode) + "):\n");
         for(var i = 0; i < x.passages.length; i++) {
            window.dump("==> " + x.passages[i]);
            window.dump("==> " + XRai.getPath(x.passages[i].parentNode) + "\n");
         }
      }
      
      // Change only if length is 2
      if (x.passages.length == 2 && !toremove) {
         if (!x.cAssessment) {
            var z = document.createElementNS(xrains,xraiatag);
            x.insertBefore(z,x.firstChild);
            var min = getMaxExh(z);
            if (min == max_exhaustivity) z.setAttribute("a",max_exhaustivity);
            else {
               z.setAttribute("a","U");
               changed += 1;
               cardUnknownAssessment++;
            }
            x.cAssessment = z;
            current = z;
         } else current = x.cAssessment;
         toremove = x.passages[0];
      } else if (x.passages.length > 2) break;
      x = XRai.parent(x);
   }
}

// To be called BEFORE the passage is removed
function removedPassage(p) {
   // Check for changed
   if (p.hasAttribute("old")) {
      if (p.getAttribute("old") == xraia2int(p)) changed += 1;
      passagesToRemove.push(p);
      p.oldParent = p.parentNode;
   } else {
      changed -= 1;
   }
   updateSaveIcon();
}

/** Assess the current selection */
function assess(e,a,the_event) {
   if (the_event.target.className == "disabled") {
      the_event.stopPropagation();
      return true;
   }

   // The judge clicked "too small below"
   if (a == "nobelow") {
      the_event.stopPropagation();
      if (currentAssessed && currentAssessed.firstSubpassage) { alert("BUG!!! Element has assessed subpassages."); return; }

      nobelow = !nobelow;
      var x = document.getElementById("nobelow");
      if (nobelow) x.className = "on";
      else x.className = null;

      if (currentAssessed) {
         var oldA = xraia2int(currentAssessed);
         if (nobelow) currentAssessed.setAttribute("nobelow","");
         else currentAssessed.removeAttribute("nobelow");
         checkAssess(currentAssessed);

         var newA = xraia2int(currentAssessed);
         var savedA = currentAssessed.getAttribute("old");

         if (currentAssessed.hasAttribute("old")) {
            window.dump("Below update: " + oldA + " - " + newA + " / " + savedA + "\n");
            if (oldA == savedA && newA != savedA) changed += 1;
            else if (oldA != savedA && newA == savedA) changed -= 1;
        }
      }
   }

   // The user assessed the element
   else {
      var ts = get_time_string();
   
   window.dump("Assessing with " + a + "\n");
   
   // Otherwise
   var selected = document.getElementsByName("sel");
   if (selected.length > 0 && currentAssessed != null) {
      alert("There is a selected passage and a current element to assess. This is a bug!");
      return;
   }
   if (selected.length == 0 && currentAssessed == null) {
      alert("There is no selected passage and no current element to assess. This is a bug!");
      return;
   }
   
   if (selected.length > 0) {
      if (a == "0") {
         clear_selected();
      } else {
         // The passage was assessed
   
         // Create an xrai tag and add it to the currentPassage children
         currentAssessed = document.createElementNS(xrains,xraiatag);
         if (currentPassage) {
            currentAssessed.nextPassage = currentPassage.firstSubpassage;
            currentAssessed.parentPassage = currentPassage;
            currentPassage.firstSubpassage = currentAssessed;
         }
         currentAssessed.setAttribute("a",a);
   
         // Insert <xrai:a> just before the first child of the first selected element
         currentAssessed.lastElement = selected[selected.length-1];
         selected[0].insertBefore(currentAssessed,selected[0].firstChild);
         if (nobelow) currentAssessed.setAttribute("nobelow"); else currentAssessed.removeAttribute("nobelow");
         setPassage(currentAssessed,true);
         // Update the container if first level
         if (!currentPassage) updateContainers(currentAssessed);
         if (a == "U") cardUnknowAssessment++;
         changed += 1; // One change
         checkAssess(currentAssessed);
      }
   } else {
   
      // The passage is only re-assessed
      if (a != "0") {
         setAssessment(currentAssessed, a);
         checkAssess(currentAssessed);
      } else {
         // Null assessment => remove the passage
         // Clear selection
      
         // Remove the assessment from the list of passages of currentPassage
         if (currentPassage)
            if (currentPassage.firstSubpassage == currentAssessed) currentPassage.firstSubpassage = currentAssessed.nextPassage;
            else {
            for(var p = currentPassage.firstSubpassage; p != null; p = p.nextPassage)
               if (p.nextPassage == currentAssessed) {
                  p.nextPassage = currentAssessed.nextPassage;
                  break;
               }
            }
      
         // Update the containers: remove the element from "passages" array of ancestors
         var toremove = currentAssessed;
         var toadd = null;
         for(var x = currentAssessed.parentNode; x != null; x = XRai.parent(x)) {
            if (x.passages && removeFromArray(x.passages, toremove)) {
               if (toadd) x.passages.push(toadd);
               if (x.passages.length == 1 && !toadd) {
                  removedPassage(x.cAssessment);
                  x.removeChild(x.cAssessment);
                  toremove = x.cAssessment;
                  x.cAssessment = null;
                  toadd = x.passages[0];
               }
            }
         }
         
         // Clear the passage and remove the assessment
         setPassage(currentAssessed,false);
         
         removedPassage(currentAssessed);
         currentAssessed.parentNode.removeChild(currentAssessed);
      } // End of remove passage
   }

   // Check for parent passage
   if (currentPassage) {
      var f = checkAssess(currentPassage);
      document.getElementById("imgMissing").style.display =  f ? "none" : null;
   }

   currentAssessed = null;
  }

  updateSaveIcon();
  updateTodo();
  updateAssessedDocument();
}

function setText(x,t) {
   if (typeof x == "string") x = document.getElementById(x);
   x.replaceChild(document.createTextNode(t), x.firstChild);
}



// Set the image
// v = -1 => disabled
// v = 1 => nok
// v = 2 => ok
function setFinished(v) {
   v = parseInt(v);
   var t = document.getElementById("finishImg");
   docStatus = v;
   switch(v) {
      case -1:
         t.setAttribute("src",base_url + "/img/disabled_nok.png");
         break;
      case 1:
         t.setAttribute("src",base_url + "/img/nok.png");
         break;
      case 2:
         t.setAttribute("src",base_url + "/img/ok.png");
         break;
      default: alert("Bug. setFinished called with invalid argument: " + v);
   }
}

// Called when the user clicked on "finish"
function onFinishClick() {
   var t = document.getElementById("finishImg");
   if (!docStatus) return;
   setFinished(3 - docStatus);
   updateSaveIcon();
}

function updateAssessedDocument() {
   setText("MissingA",cardMissingAssessment);
   setText("UnknownA",cardUnknownAssessment);
   var t = document.getElementById("finishImg");
   if (!t.status) t.status = docStatus;
   if (cardUnknownAssessment + cardMissingAssessment != 0) {
      setFinished(-1);
   } else {
      if (docStatus != 2) setFinished(1); else setFinished(2);
   }
}

function setMissing(x,b) {
   var old = x.hasAttribute("missing");
   if (b == old) return;
   if (b) {
      x.setAttribute("missing",1);
      cardMissingAssessment += 1;
      // Propagate up
      for(x = x.parentPassage; x; x = x.parentPassage) {
         if (!x.deepmissing) { x.deepmissing = 1; x.setAttribute("deepmissing",1); }
         else x.deepmissing++;
      }
   } else {
      x.removeAttribute("missing");
      cardMissingAssessment -= 1;
      for(x = x.parentPassage; x; x = x.parentPassage) {
         if (debug) window.dump("deepmissing is " + x.deepmissing + " for " + XRai.getPath(x.parentNode) + "\n");
         if (--x.deepmissing == 0) {
            x.removeAttribute("deepmissing");
         }
      }
   }
   if (cardMissingAssessment < 0) alert("Bug: # of missing assessments is < 0. You should reload the view (after saving if necessary).");
}

function setAssessment(x,a) {
   var oldA = x.getAttribute("a");
   if (oldA != a) {
      var oldintA = xraia2int(x);
      x.setAttribute("a",a);
      var savedA = x.getAttribute("old");
      var newA = xraia2int(x);
      window.dump("olda=" + oldintA + ", newA=" + newA + ", savedA=" + savedA + "\n");
      if (x.hasAttribute("old")) {
         if (oldintA == savedA && newA != savedA) changed += 1;
         else if (oldintA != savedA && newA == savedA) changed -= 1;
      }

      if (a == "U") cardUnknownAssessment++;
      else if (oldA == "U") cardUnknownAssessment--;
      if (cardUnknownAssessment < 0) alert("Bug: # of unknown assessments is < 0. You should reload the view (after saving if necessary).");
   }
}

// Check the current assessment of an element x
// Put the current attribute ("missing") if assessments are not complete (or if below is not complete)
function checkAssess(x) {
   if (x.getAttribute("a") == "U" || x.hasAttribute("nobelow") || x.lastElement == x.parentNode) {
      setMissing(x,false);
      return true;
   }
   var sum = getMinExhBelow(x,true);
   var f = sum >= parseInt(x.getAttribute("a"));
   
   window.dump("Check assess => " + XRai.getPath(x.parentNode) + " has a=" + x.getAttribute("a") + " > sum = " + sum + "\n");

   if (f) setMissing(x,false);
   else setMissing(x,true);
   return f;
}






function article_beforeunload(event) {
  if (changed> 0)
    return "X-Rai warning: " + (changed > 1 ? changed + " assessment are" : "1 assessment is") + " not saved.";
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
   return s;
}





// ==================
// ================== List of todos
// ==================

var todo = new Array();

function updateTodo() {
}

function check_todo(list,way) {
    if (!list || list.length == 0) {
        if (confirm("No more elements to assess in this view. Do you like to jump to the " + way + " view where there is an element to assess ?")) {
            window.location = "article.php?view_jump=1&id_pool=" + id_pool + "&next=" + (way == "next" ? "1" : "0") + "&view_xid=" + view_xid;
        }
        return false;
    }
   return false;
}



// ==================
// ================== Loading/saving assessments
// ==================


var toSave;

function createHiddenInput(name,value) {
   var x = document.createElement("input");
   x.setAttribute("type","hidden");
   x.setAttribute("name",name);
   x.setAttribute("value",value);
   return x;
}

var saveForm = null;

function hasChanged() {
   return (changed > 0) || docStatus != oldDocStatus;
}

function setSavingMessage(txt) {
  var saving_message = document.getElementById("saving_message");
  saving_message.replaceChild(document.createTextNode(txt), saving_message.firstChild);
}


function saved(b) {
   if (saveForm) {
      saveForm.parentNode.removeChild(saveForm);
      saveForm = null;
   }
   if (b) {
      passagesToRemove = new Array();
      changed=0;
      oldDocStatus = docStatus;
      for(var i = 0; i < toSave.length; i++) toSave[i][0].setAttribute("old",toSave[i][1]);
      updateSaveIcon();
   }
   document.getElementById('saving_div').style.visibility = 'hidden'

}

function updateSaveIcon() {
  var save = document.getElementById("save");
  if (hasChanged()) { save.src = baseurl + "img/filesave.png"; save.setAttribute("title",changed + " assessment(s) to save");  }
  else { save.src =  baseurl + "img/filenosave.png"; save.setAttribute("title","No assessment to save"); }
}

function xraia2int(res) {
   var aString = res.getAttribute("a");
   if (aString == "U") a = res.hasAttribute("nobelow") ? -1 : 0;
   else  a = !res.hasAttribute("nobelow") ? parseInt(aString) : -parseInt(aString)-1;
   return a;
}

/*

   Save assessments
   
*/
function save_assessments() {
  if (!hasChanged()) {
   display_message("notice","Nothing to save");
   return;
  }
  
  if (saveForm != null) {
   display_message("warning","Another save of assessments is being processed");
   return;
  }

  var saving_div = document.getElementById("saving_div");
  var saving_message = document.getElementById("saving_message");
  var saving_iframe = document.getElementById("assessing");
  if (!saving_iframe) alert("Hmmm. Bug! Can't retrieve the iframe 'assessing' for assessing");
   
   // Prepare the frame

  setSavingMessage("Preparing assessments");
  saving_div.style.visibility = "visible";

  // Static information
  saveForm = document.createElement("form");
  saveForm.style.display = "none";
  saveForm.setAttribute("target","xrai-assessing");
  saveForm.setAttribute("action",base_url + "/assess.php");
  saveForm.setAttribute("method","post");
  saveForm.appendChild(createHiddenInput("id_pool",id_pool));
  saveForm.appendChild(createHiddenInput("collection",xrai_collection));
  saveForm.appendChild(createHiddenInput("file",xrai_file));
  saveForm.appendChild(createHiddenInput("aversion",aversion));
  saveForm.appendChild(createHiddenInput("docstatus",docStatus));


  // Add assessments
  toSave = new Array();
  var result = xpe.evaluate(".//xrai:" + xraiatag, document.getElementById("inex"), nsResolver, 0, null);
  var res;
  while (res = result.iterateNext()) {
     a = xraia2int(res);
     if (res.getAttribute("old") == a) {
      window.dump("Skipping " + s + "\n");
     } else {
      toSave.push(new Array(res, a));
      var s = "," + (res.getAttribute("old") ? 1 : 0) + "," + a + "," + XRai.getPath(res.parentNode);
      if (res.lastElement) s += "," + XRai.getPath(res.lastElement);
      window.dump("Adding " + s + "\n");
      saveForm.appendChild(createHiddenInput("a[]",s));
     }
  }

  // Add to remove
  for(var i = 0; i < passagesToRemove.length; i++) {
    res = passagesToRemove[i];
    var s = "," + XRai.getPath(res.oldParent);
    if (res.lastElement) s += "," + XRai.getPath(res.lastElement);
    saveForm.appendChild(createHiddenInput("r[]",s));
  }
  
  // Submit
  document.getElementById("body").appendChild(saveForm);
  setSavingMessage("Connecting to server...");
  saveForm.submit();
}


// Sort function for a list of xrai:a elements
function sortF (x,y) {
   var a = x.parentNode.compareDocumentPosition(y.parentNode);
   if (a &  Node.DOCUMENT_POSITION_FOLLOWING) return -1;
   if (a & Node.DOCUMENT_POSITION_PRECEDING) return 1;
   if (!x.lastElement) return -1; if (!y.lastElement) return 1;

   a = x.lastElement.compareDocumentPosition(y.lastElement);
   if (a & Node.DOCUMENT_POSITION_FOLLOWING) return 1;
   if (a & Node.DOCUMENT_POSITION_PRECEDING) return -1;
}

/** Loads the assessment into the document */
function XRaiLoad() {
   this.loadErrors = 0;
   this.root = null;
   this.nsResolver =  null;
   this.list = new Array();
   
   this.begin =  function() {
      for(this.root = document.getElementById("inex").firstChild; this.root && this.root.nodeType != Node.ELEMENT_NODE; this.root = this.root.nextSibling) {}
      if (!this.root) { this.loadErrors = -1; return; }
      this.nsResolver = xpe.createNSResolver(this.root);
      window.dump("Root node is " + XRai.getPath(this.root) + "\n");
   };

   this.add = function(start,end,exh) {
      if (!this.root) return;
//       alert("coucou".replace("/o/g","a"));
      start = start.replace(/\//g,"/xraic:");
      end = end.replace(/\//g,"/xraic:");
      window.dump("Adding " + exh + " (" + start + " - " + end + ")\n");
      var eStart = xpe.evaluate("." + start, this.root.parentNode, this.nsResolver, 0, null).iterateNext();
      var eEnd = end == "" ? null : xpe.evaluate("." + end, this.root.parentNode, this.nsResolver, 0, null).iterateNext();
      if (!eStart || (end != "" && !eEnd)) {
         this.loadErrors++;
      } else {
         var a = document.createElementNS(xrains,xraiatag);
         a.setAttribute("old",exh);
         if (exh < 0) a.setAttribute("nobelow","");
         if (exh < -1) exh = -exh-1;
         if (exh == 0 || exh == -1) exh = "U";
         a.setAttribute("a",exh);
         eStart.insertBefore(a, eStart.firstChild);
         a.lastElement = eEnd;
         if (exh == "U") cardUnknownAssessment++;
         this.list.push(a);
      }
   };

   // Returns true if x is a subpassage of y
   // A: two passages can overlap only if they are nested
   this.is_in = function(x,y) {
      var a = y.parentNode.compareDocumentPosition(x.parentNode);
      var b = y.lastElement.compareDocumentPosition(x.lastElement);
      
      return (a == 0 || (a & Node.DOCUMENT_POSITION_FOLLOWING)) && (b == 0 || (b & Node.DOCUMENT_POSITION_PRECEDING));
   };

   
   this.end = function() {
      if (this.loadErrors != 0) alert("Error while adding existing assessments to this document. You MUST NOT assess this file");
      this.list.sort(sortF);
      if (debug) {
         window.dump("** Ordered list of passages:\n");
         for(var i = 0; i < this.list.length; i++) {
            window.dump(XRai.getPassagePaths(this.list[i]) + "\n");
         }
      }
      var stack = new Array(); // invariant = an element is contained by its predecessor
      for(var i = 0; i < this.list.length; i++) {
         res = this.list[i];
         // (1) the element is a container
         if (!res.lastElement) res.parentNode.cAssessment = res;
         else {
            // (2) The element is contained by the stack top?
            var flag = false;
            var last = null; // Contains the last removed subpassage (ie, our predecessor)
            var top = null;
            while (stack.length > 0 && !this.is_in(res,top=stack[stack.length-1])) last = stack.pop();
            if (stack.length > 0) {
               if (top.hasAttribute("nobelow")) {
                  window.dump("Top is " + XRai.getPassagePaths(top) + " / " + XRai.getPassagePaths(res));
                  alert("Document assessments are corrupted. Do not assess this file and report the problem");
                  return;
               }
               if (last != null) res.nextPassage = last;
               res.parentPassage = top;
               top.firstSubpassage = res;
               res.setAttribute("hidden","1");
            } else {
               // Top level passage
               updateContainers(res);
               setPassage(res,true);
            }
            stack.push(res);
         }
      }
      for(var i = 0; i < this.list.length; i++) {
         checkAssess(this.list[i]);
      }
     updateAssessedDocument();
   };
}




// ===================================================
       
// Print XML positions
function print_xml_positions_r(x) {
   if (x.firstChild) print_xml_positions_r(x.firstChild);
   if (x.nextSibling) print_xml_positions_r(x.nextSibling);
   if (x.nodeType != 1) return; // if x is not a XML element
   if (x.getAttribute("class") == "xmle") {
      window.dump("Found id " + x.id + " - " + x.offsetLeft + "-" + (x.offsetLeft + x.offsetWidth) + "," + x.offsetTop+ "-" + (x.offsetTop + x.offsetHeight) + "\n");
   }
}

