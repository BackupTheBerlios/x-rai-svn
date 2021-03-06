// kate: encoding iso-8859-1; indent-mode cstyle
/*
    article.js
    Javascript code for assessments
    
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

// Javascript code
// Article view javascript code
// (c) B. Piwowarski, 2004-2005

// -*- Parts (@p__ to search)
// p1: Misc
// p2: Events
// p3: internal navigation
// p4: highlighting part
// p5: assessing
// p6: load & save
// p7: user navigation

XRai.loaded = false;
XRai.noSave = !write_access; // no save prevent the assessments from being saved (error)...
var htmlns = document.documentElement.namespaceURI;

// Find the position of an XML node in the page:
//    var x = document.getBoxObjectFor(event.target);
//    if (debug) XRai.debug("====!=====> " + x + "," + x.screenX + "," + x.screenY + "," + x.x + "," + x.y + "\n");


XRai.lastX = 0;
XRai.lastY = 0;

// -*- For load and save

// Number of changes since last save
XRai.changeCount = 0;
// Assessments which were definitively deleted
XRai.assessmentsToRemove = new Array();
// User history
XRai.history = new Array();
// Current saving form
XRai.saveForm = null;
// Assessments which will be saved
XRai.toSave = null;

// Number of elements to assess
XRai.toAssess = new Array();
// Current asssessed element
XRai.currentAssessed = null;

// The current view
XRai.currentView = null;

// The first highlighted passage in document order
XRai.firstPassage = null;
XRai.firstOldPassage = null;

var xpe = null;
var nsResolver = null;


XRai.debug = function(s) {
   if (window.dump) dump(s);
   else if (window.opera) opera.postError(s);
}

XRai.error = function(s) {
   if (window.dump) dump("ERROR: " + s);
   else if (window.opera) opera.postError("ERROR: " + s);
}


// docStatus (and saved value oldDocStatus)
// 0 => highlighting mode
// 1 => assessing mode
// 2/3 => finished (no relevant / relevant)

XRai.HIGHLIGHTING = 0;
XRai.ASSESSING = 1;
XRai.FINISHED = 2;

// ==========  @p1
// ========== Misc
// ==========

// Add action a with elements x and y
XRai.addHistory = function(id, x, y) {
   XRai.history.push(new Array(id,XRai.getTimeString(),x,y));
}

// *** Add/Remove a value in an array
// A: no duplicate in the array
// A: the elements of the array are elements

XRai.addToArray = function(a,x) {
   for(var i = 0; i < a.length; i++)
      if (a[i] == x) return false;
   a.push(x);
   return true;
}

// Returns true if the element was found
XRai.removeFromArray = function(a,x) {
   for(var i = 0; i < a.length; i++) {
      if (a[i] == x) {
         a[i] = a[a.length-1];
         a.pop();
         return true;
      }
   }
   return false;
}

// *** Add/Remove an element in an ordered array
// A: the elements of the array are elements with comparable positions
// Add an id to the array


XRai.addElementToArray = function (a,x) {
   var i = 0;
   while ((i < a.length) && (compareDocumentPosition(x, a[i]) & DOCUMENT_ORDER_BEFORE)) i++;
   if (a[i] != x) a.splice(i,0,x);
}

// Remove an id from the array
XRai.removeElementFromArray = function (a,x) {
   var id = parseInt(x.id);
   var i = 0;
   while (i < a.length && (compareDocumentPosition(x, a[i]) & DOCUMENT_ORDER_BEFORE)) i++;
   while (i < a.length && !(compareDocumentPosition(x, a[i]) & DOCUMENT_ORDER_AFTER)) {
      if (a[i] == x) { a.splice(i,1); break; }
      i++;
   }
}


XRai.updateArray = function(addingMode, a, x) {
   if (addingMode) return XRai.addToArray(a,x);
   return XRai.removeFromArray(a,x);
}


XRai.toggleAttribute = function(e,x) {
   if (e.hasAttribute(x)) e.removeAttribute(x);
   else e.setAttribute(x,"1");
}

XRai.incrementAttribute = function(e,x) {
   var a = e.getAttribute(x);
   var b = a; // dbg
   a = a ? parseInt(a) : 0;
   e.setAttribute(x,a+1);
//    if (debug) XRai.debug("[A+] " + b + ". " + XRai.getPath(e) + " - " + x + " - " + e.getAttribute(x) + "\n");
}

XRai.decrementAttribute = function(e,x) {
   var a = e.getAttribute(x);
   var b = a; // dbg
   a = a == null ? -1 : parseInt(a) - 1;
   if (a == 0) e.removeAttribute(x);
   else if (a > 0) e.setAttribute(x,a);
//    if (debug) XRai.debug("[A-] " + b + ". " + XRai.getPath(e) + " - " + x + " - " + a + ", "  + e.getAttribute(x) +  (e.hasAttribute(x) ? "*" : "") + "\n");
}

function lpad(c,s,l) {
   s = String(s);
   while (s.length < l)  s = c + s;
   return String(s);
}

XRai.getTimeString = function () {
   var d = new Date();
   var s = String(d.getUTCFullYear()) + lpad("0",d.getUTCMonth()+1,2) + lpad("0",d.getUTCDate(),2);
   s += lpad("0",d.getUTCHours(),2)  + lpad("0",d.getUTCMinutes(),2)  + lpad("0",d.getUTCSeconds(),2);
   return s;
}

XRai.log = function(s) {
   if (logview) {
      var div = logview.ownerDocument.createElement("div");
      div.appendChild(logview.ownerDocument.createTextNode(s));
      logview.appendChild(div);
   } else Message.show("warning","No logview!");
}

XRai.getError = function(e) {
   return e + " (line "+e.lineNumber + " in " + e.fileName + ")";
}

// ========== @p2
// ========== Events
// ==========


/** Handlers */
XRai.mousemoved = function(event) {
   XRai.lastX = event.pageX;
   XRai.lastY = event.pageY;
};

// Find the highlighted passage the element belongs to
XRai.findHighlighted = function(x) {
   while (x)
      if (!x.hasAttribute("marked")) x = XRai.parent(x);
      else return x.passage;
   return null;
}

XRai.onclick = function(event) {
   if (!event) return;
   if (docStatus && docStatus >= 1) {
      var t = event.target;
      if (t.localName == xraiatag && t.namespaceURI == xrains) {
      if (event.shiftKey) {
            if (!t.passage) XRai.toggleAttribute(t.parentNode,"selected");
         } else {
            XRai.showEvalPanel(t,event.pageX, event.pageY);
         }
      }
  }

  if (collectionOnClick) return collectionOnClick(event);
}

XRai.onmouseover = function(event) {
   var t = event.target;
   if (docStatus >= 1) {
      if (t.localName == xraiatag && t.namespaceURI == xrains && !t.passage) {
         t.parentNode.setAttribute("boxit",1);
      }
   }
};
XRai.onmouseout = function(event) {
   var t = event.target;
   if (docStatus >= 1) {
      if (t.localName == xraiatag && t.namespaceURI == xrains && !t.passage)
         t.parentNode.removeAttribute("boxit");
   }
}

XRai.keypressed = function (event) {
  var N = !event.shiftKey && !event.ctrlKey;
  var S = event.shiftKey && !event.ctrlKey;
  var C = !event.shiftKey && event.ctrlKey;
  var SC = event.shiftKey && event.ctrlKey;

   if (debug) XRai.debug("Key pressed: charchode=" + event.charCode
        + ", keycode=" + event.keyCode
      + ", which=" + event.which + ", shiftKey=" + event.shiftKey + ", ctrlKey=" + event.ctrlKey
      + ", x= " + event.pageX + "\n");

  if (N && event.which == 104) XRai.highlight();
  else if (N && event.which == 117) XRai.unhighlight();
  else if (N && event.which == 109) XRai.switchMode();
  else if (C && (event.which == 83 || event.which == 115)) XRai.save();
  else if (!C && event.which == 57) todo_previous(true);
  else if (!C && event.which == 48) todo_previous(true);
  
  // f (switch mode)
  else if (!C && event.which == 102) XRai.switchMode();
  // p (show/hide pool elements)
  else if (!C && event.which == 112) XRai.switchSupport();
  // b
  else if (!S && event.which == 98) XRai.toggleBEPMode();
  // shift+b
  else if (S && event.which == 66) XRai.setBEP(null, true);
  
  else {
   return collection_keypress(event);
  }

  return false;
}

XRai.checkBeforeUnload = function() {
}

// Check that everything is saved before allowing the user to go out of this view
XRai.beforeunload = function(event) {
   if (!event && !window.opera) return true; // only in opera we need to check before leaving

   if (XRai.hasChanged()) {
      if (event) return "X-Rai warning: " + XRai.changeCount + " change(s) were not saved";
      else {
         return window.confirm("Do you really want to quit that page?\n\nX-Rai warning: " + XRai.changeCount + " change(s) were not saved" + "\n\nClick on OK to continue, or Cancel to stay on the actual page");
      }
   }
   if (event) return;
   return true;
}


XRai.switchMode = function() {
   if (highlight_only) return;
   if (docStatus == 0) {
      if (!XRai.switchToAssess()) return;
      docStatus = 1;
   } else {
      docStatus = 0;
   }
   XRai.updateStatusIcon();
   XRai.updateSaveIcon();
}


/*
 * Switch support element display
 */
XRai.switchSupport = function() {
   var img = document.getElementById("supportImg");
   if (XRai.support_css) {
      // Change the support CSS display
      XRai.support_css.disabled = !XRai.support_css.disabled;
      img.setAttribute("src",base_url + "/img/" + (XRai.support_css.disabled ? "no" : "") + "eyes.png");
   } else alert("Problem with support element display");
}
   
// Search for the "support" stylesheet
XRai.support_css = null;
XRai.debug("Searching for support_css stylesheet\n");
if (document.styleSheets) for(var i = 0; i < document.styleSheets.length; i++) {
   if (document.styleSheets[i].title == "support_css") {
      XRai.debug("Found it. Stylesheet: " + document.styleSheets[i].title + "\n");
      XRai.support_css = document.styleSheets[i];
      XRai.support_css.disabled = false;
      break;
   }
}
   

// ========== @p3
// ========== Navigation
// ==========


if (Node.DOCUMENT_POSITION_CONTAINS) {
   compareDocumentPosition = function(x,y) {
      return x.compareDocumentPosition(y);
   }
} else {
   Node.DOCUMENT_POSITION_PRECEDING    = 2;
   Node.DOCUMENT_POSITION_FOLLOWING    = 4;
   Node.DOCUMENT_POSITION_CONTAINS     = 8;
   Node.DOCUMENT_POSITION_CONTAINED_BY = 16;


   XRai.numberNode = function(x,i) {
      x.pre = i;
      i++;
      for(var y = XRai.firstChild(x); y; y = XRai.nextSibling(y)) {
         i = XRai.numberNode(y,i);
      }
      x.post = i;
      return i;
   }

   XRai.numberNodes = function() {
      XRai.debug("Numbering the nodes");
      var n = XRai.numberNode(XRai.getRoot(),1);
      XRai.numbered = true;
   }

   compareDocumentPosition = function(x,y) {
      var v = 0;

      if (!x.pre) x = x.parentNode;
      if (!y.pre) y = y.parentNode;

      if (!x.pre || !y.pre) throw Error("Did not find node id in compareDocumentPosition");
      if (x.pre < y.pre) {
         if (x.post >= y.post) return Node.DOCUMENT_POSITION_FOLLOWING | Node.DOCUMENT_POSITION_CONTAINED_BY;
         return Node.DOCUMENT_POSITION_FOLLOWING;
      } else if (x.pre > y.pre) {
         if (x.post <= y.post) return Node.DOCUMENT_POSITION_PRECEDING | Node.DOCUMENT_POSITION_CONTAINS;
         return Node.DOCUMENT_POSITION_PRECEDING;
      }
      return 0;
   }
}

var DOCUMENT_ORDER_BEFORE = Node.DOCUMENT_POSITION_PRECEDING | Node.DOCUMENT_POSITION_CONTAINS;
var DOCUMENT_ORDER_AFTER = Node.DOCUMENT_POSITION_FOLLOWING;

/*
   Returns true if x is in y
*/
XRai.isIn = function(x, y) {
/*   if (debug)
   XRai.debug(XRai.getPath(x) + " is "
       + (compareDocumentPosition(x,y) & Node.DOCUMENT_POSITION_CONTAINS ? "" : " NOT ")
       + " in " + XRai.getPath(y) + " / " + (compareDocumentPosition(x,y) + "&" + Node.DOCUMENT_POSITION_CONTAINS) + "\n");*/
   return compareDocumentPosition(x,y) & Node.DOCUMENT_POSITION_CONTAINS;
}
XRai.isBefore = function(x, y) {
   return compareDocumentPosition(x,y) & Node.DOCUMENT_POSITION_FOLLOWING;
}
XRai.isBeforeOrContains= function(x, y) {
   return compareDocumentPosition(x,y) & (Node.DOCUMENT_POSITION_FOLLOWING | Node.DOCUMENT_POSITION_CONTAINED_BY);
}
XRai.isAfter = function(x, y) {
   return compareDocumentPosition(x,y) & Node.DOCUMENT_POSITION_PRECEDING;
}

XRai.isBetween = function(x,a,b) {
//   if (debug) XRai.debug(compareDocumentPosition(x,a) + "&" +  Node.DOCUMENT_POSITION_PRECEDING + " && " + compareDocumentPosition(x,b) + "&" + Node.DOCUMENT_POSITION_FOLLOWING + " - " + (x==a) + " / " + (x==b) + "\n");
   return (x==a) || (x==b) ||
       ((compareDocumentPosition(x,a) & Node.DOCUMENT_POSITION_PRECEDING) && (compareDocumentPosition(x,b) & Node.DOCUMENT_POSITION_FOLLOWING));
}


// Get the true XML container of the current element
XRai.getContainer = function(x,b) {
   while (x && !XRai.isInDocument(x,b)) x = x.parentNode;
   return x;
}

XRai.isInDocument = function(x,b) {
   return (x.nodeType == Node.ELEMENT_NODE)
      && ((x.namespaceURI == documentns) || ((!b) && (x.namespaceURI == xrains) && (x.localName == "s")));
}


XRai.parent = function(e,b) {
   do e = e.parentNode;
   while (e && (!XRai.isInDocument(e,b)));
   return e;
}

XRai.nextSibling = function(e,b) {
   do e = e.nextSibling; while (e && (!XRai.isInDocument(e,b)));
   return e;
}

XRai.firstChild = function(e,b) {
   e = e.firstChild;
   while (e && (!XRai.isInDocument(e,b))) e = e.nextSibling;
   return e;
}

XRai.lastChild = function(e,b) {
   e = e.lastChild;
   while (e && (!XRai.isInDocument(e,b))) e = e.previousSibling;
   return e;
}

XRai.previousSibling = function(e,b) {
   e = e.previousSibling;
   while (e && (!XRai.isInDocument(e,b))) e = e.previousSibling;
   return e;
}


/** Return the previous element (document order): previous sibling or parent */
XRai.previous = function(x,b) {
   var y;
   if (y= XRai.previousSibling(x,b)) return y;
   return XRai.parent(x,b);
}

/** Return the next element: first child, next sibling or the first ancestor next sibling */
XRai.next = function(x,b) {
   var y;
   if (y= XRai.firstChild(x,b)) return y;
   while (x != null) {
      if (y = XRai.nextSibling(x,b)) return y;
      x = XRai.parent(x);
   }
   return null;
}

/** Return the previous element (document order): previous sibling or ancestor first previous sibling*/
XRai.noDirectPrevious = function(x,b) {
   var y;
   if (y= XRai.previousSibling(x,b)) return y;
   while (x != null) {
      if (y = XRai.previousSibling(x,b)) return y;
      x = XRai.parent(x);
   }
   return null;
}

/** Return the no direct next element:  next sibling or the first ancestor next sibling */
XRai.noDirectNext = function(x,b) {
   var y;
   while (x != null) {
      if (y = XRai.nextSibling(x,b)) return y;
      x = XRai.parent(x);
   }
   return null;
}

/** Returns the next XML node leaf after x */
XRai.nextLeaf = function(x,b) {
   var y = XRai.nextSibling(x,b);
   while (y == null && x != null) {
      x = XRai.parent(x);
      if (x) y = XRai.nextSibling(x,b);
   }
   if (y == null) return null;

   var z = y;
   while (z != null) { y = z; z = XRai.firstChild(y,b); }
   return y;
}

XRai.nextElementTo = function(x,y,b) {
   if (x == y) return null;
   if (y == null) return XRai.noDirectNext(x,b);

   if (!XRai.isIn(y,x)) x = XRai.noDirectNext(x);

   while (x && XRai.isIn(y,x)) x = XRai.firstChild(x,b);
   return x;
}

XRai.previousElementTo = function(x,y,b) {
   if (x == y) return null;
   if (y == null) return XRai.noDirectPrevious(x,b);

   if (!XRai.isIn(x,y)) x = XRai.noDirectPrevious(x,b);
   else {
      var z = XRai.previous(x,b);
      if (z) return z;
      return y;
   }
   while (x && XRai.isIn(y,x)) x = XRai.lastChild(x,b);
   return x;
}

XRai.getTagName = function(x) {
   if (x.tagName == "xrai_itag") return x.getAttribute("xrai_tagname");
   return x.tagName;
}

XRai.getPath = function(e) {
   if (e == null) return null;
   if (e.getPaths) return e.getPaths();
   if (!XRai.isInDocument(e)) throw new Error("!!! Can't get the path of " + e + "/" + e.tagName + "\n");
   var s = "";
   var f;
   do {
      f = XRai.parent(e);
      var n = 1;
      var tagName = XRai.getTagName(e);
      if (f) for(var x = XRai.firstChild(f); x !=  e; x = XRai.nextSibling(x)) {
         if (XRai.getTagName(x) == tagName) n++;
      }
      s = "/" + tagName + "[" + n + "]" + s;
   } while (e = f);
   return s;
}

XRai.root = null;
XRai.getRoot = function() {
   if (XRai.root == null)
      for(XRai.root = document.getElementById("inex").firstChild;
            XRai.root && XRai.root.nodeType != Node.ELEMENT_NODE; XRai.root = XRai.root.nextSibling) {}
   return XRai.root;
}


XRai.getCommonAncestor = function(x,y) {
   if (debug) XRai.debug("Getting common ancestor of " + XRai.getPath(x) + " and " + XRai.getPath(y) + "\n");
   if (!x || !y) return null;
   if (XRai.isIn(x,y)) return y;
   if (XRai.isIn(y,x)) return x;
   while (x = XRai.parent(x)) if (XRai.isIn(y,x)) return x;
   return null;
}


// Return true if the entire element x is in the range (begin of start,end of end)
// True if the element x is start, or is after start (document order) and not after end
XRai.isInRange = function(x,start,end) {
   return (x==start) || (x==end) || ((compareDocumentPosition(x,start) & DOCUMENT_ORDER_BEFORE) && (compareDocumentPosition(x,end) & DOCUMENT_ORDER_AFTER));
}

XRai.passagesOverlap = function(p1,p2) {
   if (p1.start == p2.start || p1.end == p2.end) return true;
   var c = compareDocumentPosition(p1.start,p2.start);
   // Swap p1 and p2 if p2 starts before
   if ((c & Node.DOCUMENT_POSITION_PRECEDING) && !(c & Node.DOCUMENT_POSITION_CONTAINS)) {
      var p = p1; p1 = p2; p2 = p;
   }
   var e = compareDocumentPosition(p1.end,p2.start);
   // No intersection if p2 start is after p1 end
   return !(e & Node.DOCUMENT_POSITION_FOLLOWING);
}

XRai.getFirstValidParent = function(x,b) {
   if (!XRai.isInDocument(x,b)) return XRai.parent(x,b);
   return x;
}

// ========== @p4
// ========== Highlighting
// ==========

// Passage information is contained in a passage element (xraiatag variable)
// - the parent node is the start of the passage
// - lastElement points on the last element of the passage
// - nextPassage points to the next passage (document order)
// - previousPassage points to the previous passage
// - maxBelow is the maximum exhaustivity over all descendants


// A passage can be:
// - active = highlighted
// - validated = highlighted passages after a switch are validated (and unhighlighted are erased)

// XML nodes which are within a passage are marked with attribute marked=1

// Passage object
// start/end delimit the passage
// previous/next are used to link the passage to others
// assessment is a reference to the assessment (implies that the passage was already assessed)

// Construction:
// (x,y) define the range
// savedValue is null if the element is created by the user, otherwise it is the saved value
Passage = function(x,y,savedValue) {
   // *-*- Methods

   var z = XRai.normalisePassage(x,y);
   x = z.x;
   y = z.y;

   // Get the container passage (null if == to lca)
   this.getContainer = function() {
      var range = { start: XRai.getFirstValidParent(this.start,1), end: XRai.getFirstValidParent(this.end,1) };
      if (range.start == range.end) return null;
      var c = compareDocumentPosition(range.start,range.end);
      if ((c & Node.DOCUMENT_POSITION_CONTAINS) || (c & Node.DOCUMENT_POSITION_CONTAINED_BY)) return null;
      return range;
   }

   // Validate the passage
   this.validate = function() {
      if (debug) XRai.debug("* Validating " + this.getPaths() + "\n");
      this.validated = true;

      // Update containers
      if (debug) XRai.debug("Updating containers\n");
      var lca = this.getLCA();
//       XRai.addContained(this,lca);

      // Create assessments for intersection
      var range = this.getContainer();
      if (range) {
         if (debug) XRai.debug("** Adding assessments for intersection (" + XRai.getPath(range.start) + "-" + XRai.getPath(range.end) + ")\n");
         for(var x = range.start; x; x = XRai.nextElementTo(x,range.end,1)) {
            XRai.addPassage(x,this,1);
         }
      } else XRai.addPassage(lca,this,1);

      if (debug) XRai.debug("*** Adding in[side] elements for " + this.getPaths() + "\n");
      for(var x = this.start; x; x = XRai.nextElementTo(x,this.end)) {
         if (!XRai.isInDocument(x,1)) continue;
         if (debug) XRai.debug(" [adding assessments below " + XRai.getPath(x) + "]\n");
//          if (debug) XRai.debug("In " + XRai.getPath(x) + "\n");
         for(var y = x; y && ((y == x) || XRai.isIn(y,x)); y = XRai.next(y,1)) {
            if (debug) XRai.debug("  Element " + XRai.getPath(y) + " is in passage " + this.getPaths() + "\n");
            if (!y.cAssessment) {
               y.cAssessment = XRai.newAssessment(y,"0",false);
            }
            if (y.cAssessment.hasAttribute("type")  && (y.cAssessment.getAttribute("type") != "in"))
               throw Error("The element assessment " + XRai.getPath(y) + " has already a type (" + y.cAssessment.getAttribute("type") + ")");
            y.cAssessment.setAttribute("type","in");
         }
      }
   }

   // Validate the passage
   this.unvalidate = function() {
      if (debug) XRai.debug("* Unvalidating " + this.getPaths() + "\n");
      this.validated = false;

      // Update containers
      if (debug) XRai.debug("Updating containers\n");
      var lca = this.getLCA();
//       XRai.removeContained(this,lca);
//       XRai.removeAssessment(this.assessment);

      // Remove assessments for intersection
      var range = this.getContainer();
      if (range) {
         if (debug) XRai.debug("Removing intersections\n");
         for(var x = range.start; x; x = XRai.nextElementTo(x,range.end,1)) {
            XRai.removePassage(x,this,1);
         }
      } else XRai.removePassage(lca,this,1);

      if (debug) XRai.debug("Removing inner elements\n");
      for(var x = this.start; x; x = XRai.nextElementTo(x,this.end)) {
         if (!XRai.isInDocument(x,1)) continue;
         if (debug) XRai.debug("In " + XRai.getPath(x) + "\n");
         for(var y = x; y && ((y == x) || XRai.isIn(y,x)); y = XRai.next(y,1)) {
            if (!y.cAssessment) continue;
            XRai.removeAssessment(y.cAssessment);
         }
      }

      if (typeof this.saved != "undefined") {
         XRai.assessmentsToRemove.push(this);
         var oldvalue = this.validated ? Passage.VALIDATED : Passage.UNVALIDATED;
         if (this.saved == oldvalue) XRai.changeCount++;
      } else XRai.changeCount--;

      XRai.firstOldPassage = this.remove(XRai.firstOldPassage);

      if (debug) XRai.debug("*/ Unvalidated " + this.getPaths() + "\n");
   }

   // Remove the passage from the linked list
   this.remove = function(list) {
      if (this.previous) this.previous.next = this.next;
      else {
         if (this != list) throw Error("The element should be the first of the list but it is not");
         list = this.next;
      }
      if (this.next) this.next.previous = this.previous;
      this.next = this.previous = null;
      return list;
   }

   // Add the passage from a linked list
   this.add = function(list) {
      var previous = null;
      var current = list;
      while (current && XRai.isAfter(this.start, current.start)) { previous = current; current = current.next; }

      if (previous) {
         this.next = previous.next;
         this.previous = previous;
         previous.next =  this;
      } else {
         this.next = list;
         this.previous = null;
         list = this;
      }
      if (this.next) this.next.previous = this;

      // Check for overlap
      if (debug) XRai.debug("Checking for overlap\n");
      if (this.previous && XRai.passagesOverlap(this,this.previous))
         throw Error("Passages overlap (" + this.getPaths() + " and " + this.previous.getPaths());
      if (this.next && XRai.passagesOverlap(this,this.next))
         throw Error("Passages overlap (" + this.getPaths() + " and " + this.next.getPaths());
      return list;
   }

   // Highlight the passage
   this.highlight = function() {
      if (debug) XRai.debug("Highlighting " + this.getPaths() + "\n");
      XRai.incrementAttribute(this.start,"first");
      XRai.incrementAttribute(this.end,"last");
      for(var e = this.start; e != null; e = XRai.nextElementTo(e,this.end)) {
         XRai.incrementAttribute(e,"marked");
      }
      if (debug) XRai.debug("Highlighting done\n");
   }

   this.markPath = function() {
      for(var e = this.start; e != null; e = XRai.nextElementTo(e,this.end))
         e.passage = this;
   }

   // Unhighlight this passage
   this.unhighlight = function() {
      if (debug) XRai.debug("Unhighlighting " + this.getPaths() + (this.validated ? "*" : "") + "\n");
      XRai.firstPassage = this.remove(XRai.firstPassage);

      var oldvalue = this.validated ? Passage.VALIDATED : Passage.UNVALIDATED;

      if (this.validated) {
         // If it was validated, change the active state
         XRai.firstOldPassage = this.add(XRai.firstOldPassage);
         this.active = false;
         if (typeof this.saved != "undefined") {
            if (oldvalue == this.saved) XRai.changeCount++;
            else XRai.changeCount--;
         } else XRai.changeCount--;
      } else {
         if (typeof this.saved != "undefined") {
            XRai.assessmentsToRemove.push(this);
            XRai.changeCount++;
         } else XRai.changeCount--;
      }

      // Unhighlight in the user view
      XRai.decrementAttribute(this.start,"first");
      XRai.decrementAttribute(this.end,"last");
      for(var e = this.start; e != null; e = XRai.nextElementTo(e, this.end))
         XRai.decrementAttribute(e,"marked");
   }


   this.getPaths = function() {
      return "<" + XRai.getPath(this.start) + (this.end ? "," + XRai.getPath(this.end) : "") + ">";
   }

   this.lca = null;
   this.getLCA = function() {
      if (this.lca) return this.lca;
      return this.lca = XRai.getCommonAncestor(this.start, this.end);
   }



   // -*- Initialisation

   this.start = x;
   this.end = y;
   this.isPassage = true;

   if (typeof savedValue != "undefined") this.saved = savedValue;
   else XRai.changeCount++;

   if (savedValue != null) {
      this.active = (savedValue == Passage.VALIDATED) || (savedValue == Passage.UNVALIDATED);
      this.validated = (savedValue != Passage.UNVALIDATED);
   } else {
      this.active = true;
      this.validated = false;
   }

   // Add the passage to the current list
   if (this.active) {
      XRai.firstPassage = this.add(XRai.firstPassage);
      this.highlight();
   } else XRai.firstOldPassage = this.add(XRai.firstOldPassage);


   // Find a removed passage with the same span
   if ((savedValue == null) && this.active) {
      if (debug) XRai.debug("Trying to find a removed passage with same coverage\n");
      for(var p = XRai.firstOldPassage; p && !XRai.isAfter(p.start,this.start); p = p.next) {
         if (p.start == this.start && p.end == this.end) {
            if (debug) XRai.debug("Restoring an unactive passage\n");
            XRai.firstOldPassage = p.remove(XRai.firstOldPassage);
            XRai.removeFromArray(XRai.assessmentsToRemove,this);
            return;
         }
      }
   }

   if (debug) XRai.debug("Passage " + this.getPaths() + " added\n");
}

Passage.UNVALIDATED = 3; // active & not validated
Passage.VALIDATED = 2; // active & validated
Passage.OLDVALIDATED = 1; // not active & validated

// Get selection function
if (!document.implementation.hasFeature("Range", "2.0")) {
   alert("X-Rai cannot be used on your browser (no range support)");
} else {
   if (window.getSelection) {
      XRai.getSelectedRange = function() {
         var range = null;
         var selection = window.getSelection();
         range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
         selection.collapseToStart();
         return range && range.collapsed ? null : range;
      }
   } else {
      XRai.onmousedown = function(event) {
         XRai.startselection = event.target;
         if (XRai.startselection.nodeType == Node.TEXT_NODE)
            XRai.startselection = XRai.startselection.parentNode;
//          Message.show("notice",event.target.localName + " / " + XRai.startselection.localName);
      }
      XRai.onmouseup = function(event) {
         XRai.endselection = event.target;
         if (XRai.endselection.nodeType == Node.TEXT_NODE) XRai.endselection = XRai.endselection.parentNode;
//          Message.show("notice",event.target.localName + " / " + XRai.startselection.localName);
      }

      XRai.getSelectedRange = function() {
/*         var r = document.selection.createRange();
         alert(r.parentElement);
         alert(r.collapse);
         r.collapse(false);*/
         if (compareDocumentPosition(XRai.endselection, XRai.startselection) & DOCUMENT_ORDER_BEFORE)
            return { startContainer: XRai.startselection, endContainer: XRai.endselection};
         return { startContainer: XRai.endselection, endContainer: XRai.startselection};
      }

   }
}


XRai.localise = function() {
   var sel = XRai.getSelection();
   var div = document.getElementById("infopassagediv");
   div.replaceChild(document.createTextNode("Topic id: " + id_topic + " (pool " + id_pool + "), collection '" + xrai_collection + "', file '" + xrai_file + "'"
   + (sel ? "; passage " + XRai.getPath(sel.x) + " - " + XRai.getPath(sel.y)
      : " no selected passage")
   ), div.firstChild);
   div.parentNode.style.display = "block";
}

// Return the start/end *elements* of the current selection
XRai.getSelection = function() {
   if (!writeAccess) return null;
   var range = XRai.getSelectedRange();

   if (range == null || range.collapsed) {
      Message.show("warning","No selected elements");
      return;
   }

   // Get the start and the end of the container
   var x = XRai.isInDocument(range.startContainer) ? range.startContainer :XRai.previous(range.startContainer);
   var y = XRai.isInDocument(range.endContainer) ? range.endContainer : XRai.previous(range.endContainer) ;
   if ((!x) || (!y)) { Message.show("warning","Invalid passage"); return null; }

//    if (debug) XRai.debug("*** Selected passage is " + XRai.getPath(x) + ", " + XRai.getPath(y));
   return XRai.normalisePassage(x,y);
}


/* 
   Normalise a passage

   Returns a passage (x,y) such that the start element and end element do not intersect each other, 
   except if they are equal (in this case, the passage is reduced to an element) 
*/
XRai.normalisePassage = function(x,y) {
   var z;
   // if y is the last child (or the last child last child etc.), then set y to x
   if (XRai.isIn(y,x) && !((z = XRai.noDirectNext(y)) && XRai.isIn(z,x))) y = x;
   // otherwise, go down until x is not in y anymore
   else while (XRai.isIn(y,x)) x = XRai.firstChild(x);
   return { x: x, y: y }
}


XRai.unhighlight = function() {
   // Check if we can unhighlight (status is open, write access, and there is
   // already an highlighted passage)
   if (docStatus != 0) {
      Message.show("warning","You cannot highlight a passage when the document is marked as validated\n",-1);      
   }
   if (!write_access || (docStatus != 0) || (!XRai.firstPassage)) return;
   
   var toremove = new Array();
   var toadd = new Array();
   try {
      var sel = XRai.getSelection();
      if (sel == null) return;
      var x = sel.x;
      var y = sel.y;
      XRai.addHistory("UNHIGH",x,y);
      if (debug) {
         if (debug) XRai.debug("\nUNHIGHLIGHTING START\n");
         if (debug) XRai.debug(XRai.getPath(x) + " -> " + XRai.getPath(y) + "\n");
         XRai.log("Unhighlighting: " + XRai.getPath(x) + " -> " + XRai.getPath(y));
      }

      // Find the first passage
      z = XRai.firstPassage;
      while (z && (XRai.isAfter(x,z.end) && !XRai.isIn(x,z.end)))  {
         if (debug) XRai.debug("Skipping conflicting passage: " + z.getPaths() +"\n");
         z = z.next;
      }
      if (debug && z) {
         if (debug) XRai.debug("Next conflicting passage is " + z.getPaths() +"\n");
      }

      if (!z || (y != z.start && XRai.isBefore(y,z.start) && !XRai.isIn(z.start,y))) return;

      var firstRemoved = false;

      // < overlap with z/x
      if (z && (XRai.isAfter(x,z.start) || XRai.isIn(x,z.end))) {
         // Break the node
         toremove.push(z);
         toadd.push(new Array(z.start, XRai.previousElementTo(x,z.start)));
         if (debug) XRai.debug("Intersection(" + z.getPaths() + " with start: " + XRai.getPath(x) + ") => "
               + XRai.getPath(XRai.previousElementTo(x,z.start)) + "\n");
         firstRemoved = true;
      }

      // While y is after z end
      if (debug) if (z) XRai.debug("Check for included passages to remove with y=" + XRai.getPath(y) + " and " + XRai.getPath(z.end) + "\n"); else XRai.debug("No more conflicting and included passage for removing\n");

      for(; z && (XRai.isAfter(y,z.end) || y == z.end); z = z.next) {
         if (debug) XRai.debug("Removing passage " + z.getPaths()
            + (z.next ? " - next conflict: " + z.next.getPaths() : " - no next conflict") + "\n");
         if (!firstRemoved) toremove.push(z);
         firstRemoved = false;
      }

      // Check overlap z/y
      // z must be
      if (debug) if (z) XRai.debug("Check if we have to add a segment from y=" + XRai.getPath(y) + " to " + XRai.getPath(z.end) + "\n"); else XRai.debug("No more conflicting passage for rehighlighting after passage\n");
      if (z && ((compareDocumentPosition(y, z.start) & DOCUMENT_ORDER_BEFORE) || (z.start == y))) {
         if (debug) XRai.debug("Yes. Removing last conflicting passage " + z.getPaths() + " for " + XRai.getPath(y) + "\n");
         if (!firstRemoved) toremove.push(z);
         toadd.push(new Array(XRai.nextElementTo(y,z.end),z.end));
      }

      try {
         for(var i = 0; i < toremove.length; i++) toremove[i].unhighlight();
         for(var i = 0; i < toadd.length; i++) new Passage(toadd[i][0], toadd[i][1]);
      } catch (error) {
         alert("Unrecoverable error: you should stop assessing WITHOUT SAVING and fill a bug report\n\n" + error);
         if (debug) throw error;
      }
   } catch(error) {
         alert("Recoverable error: you should stop assessing, save your assessments and fill a bug report\n\n" + error);
         if (debug) throw error;
   }

   if (debug) XRai.dumpPassages();
   XRai.updateSaveIcon();
}


XRai.highlight = function() {
   if (!write_access) return;
   if (docStatus != 0) {
      Message.show("warning","You cannot highlight a passage when the document is marked as validated\n",-1);      
      return;
   }
   try {

      var toremove = new Array();

      var sel = XRai.getSelection();
      if (sel == null) return;
      var x = sel.x;
      var y = sel.y;
      XRai.addHistory("HIGH",x,y);
      if (debug)
         XRai.addHistory("HIGH",x,y);

      if (debug) {
         if (debug) XRai.debug("\nHIGHLIGHTING START\n");
         if (debug) XRai.debug(XRai.getPath(x) + " -> " + XRai.getPath(y) + "\n");
         XRai.log("Highlighting: " + XRai.getPath(x) + " -> " + XRai.getPath(y));
      }

      // (1) Find the previous highlighted passage (can be within)
      var z = XRai.firstPassage;
      while (z && (XRai.nextElementTo(z.end,x) != x) && !XRai.isIn(x,z.end) && XRai.isAfter(x,z.end)) {
         if (debug) XRai.debug("Skipping conflicting passage is " + z.getPaths() +"\n");
         z = z.next;
      }
      if (debug && z) {
         if (debug) XRai.debug("Next conflicting passage is " + z.getPaths() +"\n");
      }

      // Check for merge
      if (z && XRai.nextElementTo(z.end,x) == x) {
         x = z.start;
      } else if (z && (XRai.isBetween(x,z.start,z.end) || XRai.isIn(x,z.end))) {
         if (XRai.isBetween(y,z.start,z.end)) {
            if (debug) XRai.debug("Highlight is within a single segment\n");
            return;
         }
         x = z.start;
      }

      // (2) Unhighlight until we are in y
      while (z && XRai.isAfter(y,z.end)) {
         toremove.push(z);
         z = z.next;
         if (z) if (debug) XRai.debug("Next conflicting passage is " + z.getPaths() +"\n");
      }

      // (3) Merge y with the current z
      if (z && (XRai.isBetween(y,z.start,z.end) || (XRai.nextElementTo(y,z.start) == z.start))) {
         y = z.end;
         toremove.push(z);
      }

      // End of highlighting = add the passage
      try {
         for(var i = 0; i < toremove.length; i++) toremove[i].unhighlight();
         new Passage(x,y);
      } catch (error) {
         alert("Unrecoverable error: you should stop assessing WITHOUT SAVING and fill a bug report\n\n" + error);
         if (debug) throw error;
         return;
      }

   } catch (error) {
      alert("Recoverable error: you should save your work and fill a bug report\n\n" + error);
      if (debug) throw error;
      return;
   }

   XRai.updateSaveIcon();
   if (debug) XRai.dumpPassages();
}

XRai.dumpPassages = function() {
   if (debug) XRai.debug("Highlighted passages are:\n");
   for(var x = XRai.firstPassage; x != null; x = x.next) {
      if (debug) XRai.debug("[A] " + x.getPaths() + "\n");
      if (x.next && x.next.previous != x) if (debug) XRai.debug("!!!\n");
   }
   for(var x = XRai.firstOldPassage; x != null; x = x.next) {
      if (debug) XRai.debug("[U] " + x.getPaths() + "\n");
      if (x.next && x.next.previous != x) if (debug) XRai.debug("!!!\n");
   }
}


XRai.hasRelevant = function() { return XRai.firstPassage != null; }


// ========== @p5
// ========== Assessing
// ==========


XRai.gotoNext = function() {
}

XRai.gotoPrevious = function() {

}

XRai.checkContainerAssessment = function(x) {
   if (debug) XRai.debug("Checking for container " + x.cAssessment + ", " + x.reallyContained + ", " + x.realIntersection + "\n");
   if (x.cAssessment && !x.realIntersection && (!x.reallyContained || x.reallyContained < 2)) {
      XRai.removeAssessment(x.cAssessment);
      return -1;
   } else if (x.realIntersection || x.reallyContained >= 2) {
      var r = 0;
      if (!x.cAssessment) {
            x.cAssessment = XRai.newAssessment(x,"0",false);
            r = 1;
      }

      if (x.realIntersection) x.cAssessment.setAttribute("intersection",1);
      else x.cAssessment.removeAttribute("intersection");

      if (x.reallyContained >= 2) x.cAssessment.setAttribute("type","container");
      else x.cAssessment.removeAttribute("type");

      return r;
   }
}

XRai.addPassage = function(x,p,b) {
   if (!x.passages) x.passages = new Array();
   if (XRai.addToArray(x.passages,p) && b) {
      if (!x.realIntersection) x.realIntersection = 1;
      else x.realIntersection++;
      if (XRai.checkContainerAssessment(x)) XRai.addContained(x,null,null); // p.getLCA());
   }
}

XRai.removePassage = function(x,p,b) {
   if (!x.passages) return;
   if (debug) XRai.debug("Remove passage " + p.getPaths() + " from " + XRai.getPath(x) +"\n");
   if (XRai.removeFromArray(x.passages,p) && b) {
      x.realIntersection--;
      if (XRai.checkContainerAssessment(x) != 0) XRai.removeContained(x, null, null) ; //p.getLCA());
   }
}

XRai.addContained = function(z, x, limit) {
   var toremove = null;
   var toadd = z;
   if (debug) XRai.debug("\n");


   if (!x)
      if (z.passage) x = XRai.getCommonAncestor(z.passage.start,z.passage.end);
      else x = XRai.parent(z,1);

   if (debug) XRai.debug("Adding contained for " + XRai.getPath(z) + " / " + XRai.getPath(x) + " limit=" + (limit?XRai.getPath(limit):"") +"\n");
   while (x) {
      if (!x.containers) { x.containers = new Array(); x.reallyContained = 0; }
      else if (toremove) XRai.removeFromArray(x.containers,toremove);
      x.containers.push(toadd);
      if (debug) {
         if (debug) XRai.debug("Element " + XRai.getPath(x) + " has [" + x.containers.length + "/"
               + x.reallyContained + (!limit || XRai.isIn(x,limit) ? "*" : "")+ "] ref. to (remove="
               +  (!toremove ? "null" : XRai.getPath(toremove))
               + ", add=" + XRai.getPath(toadd) + "):\n");
         for(var i = 0; i < x.containers.length; i++) {
            if (debug) XRai.debug("==> " + XRai.getPath(x.containers[i]) + "\n");
         }
      }

      // Change only if (real) length is 2

      if (!limit || XRai.isIn(x,limit)) {
         if (!toremove) x.reallyContained++;
         if (x.reallyContained > x.containers.length)
            throw Error("Number of really contained is strictly superior to the number of contained in addContained()");
         XRai.checkContainerAssessment(x);
      }
      if (x.containers.length == 2 && !toremove) {
         toremove = x.containers[0];
         toadd = x;
      } else // no need to update further
         if (x.containers.length >= 2) break;

      x = XRai.parent(x,1);
   }
}

XRai.removeContained = function(z, x, limit) {
   if (!x)
      if (z.passage) x = XRai.getCommonAncestor(z.passage.start,z.passage.end);
      else x = XRai.parent(z,1);
   var toremove = z;
   var toadd = null;
   if (debug) XRai.debug("Removing contained for " + XRai.getPath(z) + " / " + XRai.getPath(x) + " limit=" + (limit?XRai.getPath(limit):"") +"\n");

   for(; x != null; x = XRai.parent(x,1)) {
      XRai.removeFromArray(x.containers, toremove);
      if (debug) {
         if (debug) XRai.debug("Element " + XRai.getPath(x) + " has [" + x.containers.length + "/"
         + x.reallyContained + (!limit || XRai.isIn(x,limit) ? "*" : "")+ "] ref. to (remove="
         +  (!toremove ? "null" : XRai.getPath(toremove))
               + ", add=" + XRai.getPath(toadd) + "):\n");
         for(var i = 0; i < x.containers.length; i++) {
            if (debug) XRai.debug("==> " + XRai.getPath(x.containers[i]) + "\n");
         }
      }
      if (toadd) x.containers.push(toadd);
      if (!limit || XRai.isIn(x,limit)) {
         if (!toadd) x.reallyContained--;
         if (x.reallyContained > x.containers.length)
            throw Error("Number of really contained is strictly superior to the number of contained in removeContained()");
         XRai.debug("In " + XRai.getPath(x) + ", remaining = " + x.containers.length + " / " + x.reallyContained  + (toadd ? "*" : "") + "\n");
         XRai.checkContainerAssessment(x);
      }
      if (x.containers == 1 && !toadd) {
         toremove = x;
         toadd = x.containers[0];
      }
      if (x.containers >= 2) break;
   }
}


// Called to switch the view
XRai.switchToAssess = function() {
   if (!write_access) return;
   if (highlight_only) return true;
   try {
      // (1) Invalidate
      if (XRai.firstOldPassage) {
         for(var p = XRai.firstOldPassage; p; p = p.next)
            p.unvalidate();
      }

      // (2) Add passages
      XRai.passagesToAssess = 0;
      for(var p = XRai.firstPassage; p; p = p.next)
         if (!p.validated) p.validate();

      docStatus = 1;
      XRai.updateSaveIcon();
   } catch(error) {
      alert("An error occured during the switch. Please report the bug and STOP your assessments");
      if (debug) XRai.debug("ERROR: " + XRai.getError(error) + "\n");
      throw error;
   }
   return true;
}



/** Show the eval panel */
XRai.showEvalPanel = function(jtag,px,py) {
  var eval = document.getElementById("eval_div");

   // Check for valid assessments
   // ie a >= max(children) && <= any ancestor
   var max=max_exhaustivity;
   var min = 1;
//    if (debug) XRai.debug("Assessement must be in [" + min + "," + max + "]\n");

   // Disable invalid assessements
//    for(var i = 1; i <= max_exhaustivity; i++) {
//       var x = document.getElementById("assess_" + i);
//       x.className = (i >= min && i <= max ? null : "disabled");
//    }

  // Too small access
  document.getElementById("assess_-1").className = jtag.passage || XRai.containsPassage(jtag.parentNode) ? "disabled" : null;

  // No below ?
  var nb = document.getElementById("nobelow");

  // Go down
  show_div_xy(px,py,   "eval_div");

  XRai.currentAssessed = jtag;
  window.status = "Evaluating " + (jtag.passage ? jtag.passage.getPaths() : XRai.getPath(jtag.parentNode));
  return true;

}

/** The user has assessed the passage / element */
XRai.assess = function(img, a, event) {
   try {
      if (img.className == "disabled") { event.stopPropagation(); return; }

      var olda = XRai.currentAssessed.getAttribute("a");
      if (olda != a) {

         // Check consistency below
         if (a == "-1") XRai.setMaxBelow(XRai.currentAssessed,-1,true);
         else if (olda == "-1") XRai.setMaxBelow(XRai.currentAssessed,0);
         else if (a < max_exhaustivity) XRai.setMaxBelow(XRai.currentAssessed,a);

         XRai.setAssessment(XRai.currentAssessed,a,false);
         XRai.updateSaveIcon();
      }

   } catch(e) {
      alert("Error while assessing (line "+e.lineNumber + " in " + e.fileName + "). You should report the bug and STOP assessing");
      if (debug) XRai.debug("ERROR: " + e + "\n");
      throw e;
   }
   return true;
}


/** Check for constistency, going upward
   If the newa exhaustivity is equal to maximum, also set to maximum
*/
XRai.upwardCheck = function(x,olda, newa, ca,updateOnly) {
   if (!x.statistics) {
      x.statistics = new Array();
      for(var i = 0; i <= max_exhaustivity; i++) x.statistics.push(0);
   }
   if (olda > 0) { x.statistics[olda]--; if (debug) XRai.debug(" For " + olda + " => " + x.statistics[olda] + "\n"); }
   if (newa > 0) { x.statistics[newa]++; if (debug) XRai.debug(" For " + newa + " => " + x.statistics[newa] + "\n"); }
      // Check for reset
   if (ca && !updateOnly) {
      var a = ca.getAttribute("a");
         // Did it break support?
      if (olda >= a && newa < a) {
         var f = false;
         for(var i = a; i <= max_exhaustivity && !f; i++)
         if (x.statistics[i] > 0) f = true;
         if (!f) XRai.setAssessment(ca,"0");
      }
   }

   if (ca && newa > a) {
      if (newa == max_exhaustivity) XRai.setAssessment(ca,newa);
      else XRai.setAssessment(ca,"0");
   }

}

XRai.upwardUpdate = function(x, olda, newa, updateOnly, skip) {
   if (!skip) {
      if (debug) XRai.debug("Updating (upward) " + XRai.getPath(x) + ": " + olda + " -> "  + newa + "\n");
      if (x.cAssessment) XRai.upwardCheck(x, olda, newa, x.cAssessment, updateOnly);
   }
//       else {
//       // Is it contained in a passage ?
//       var y = x;
//       while (y && y.cAssessment) {
//          if (debug) XRai.debug("Looking for passage (" + XRai.getPath(y) + " IN=" + (y.passages ? y.passages.length : "null") + ")\n");
//          if (y.passages && y.passages.length > 0) {
//             for(var i = 0; i < y.passages.length; i++) {
//                var p = y.passages[i];
//                if (XRai.isInRange(x,p.start, p.end)) {
//                   if (debug) XRai.debug(XRai.getPath(x) + " is in passage " + p.getPaths() + " with a = " + p.assessment.getAttribute("a") + "\n");
//                   XRai.upwardCheck(p, olda, newa, p.assessment, updateOnly);
//                } else if (debug) XRai.debug(XRai.getPath(x) + " is NOT in passage " + p.getPaths() + "\n");
//             }
//             break;
//          } else if (debug) XRai.debug(XRai.getPath(x) + " is not an intersection\n");
//
//          if (y.cAssessment.getAttribute("type") != "in") {
//             if (debug) XRai.debug("We are not within a passage anymore:" + y.cAssessment.getAttribute("type") + "!\n");
//             break;
//          }
//          y = XRai.parent(y,1);
//       }
//    }

   // Go up
   x = XRai.parent(x,1);
   if (x) XRai.upwardUpdate(x, olda, newa, updateOnly, false);
}

/** The user choose nobelow
   Returns false if the element is a passage or contains a passage
   and true otherwise
*/
XRai.setBound = function(x, bound, force) {
   if (!x) return;
   if (bound == -1 && (force || (x.getAttribute("a") == "0"))) {
      var y = x.parentNode;
      if ((y.realIntersection > 0 || y.reallyContained > 0) && XRai.containsPassage(y)) return;
      XRai.setAssessment(x,-1);
   } else if (bound == 0 && x.getAttribute("a") == "-1") XRai.setAssessment(x,"0");
   else if (bound > 0 && x.getAttribute("a") > bound) XRai.setAssessment(x,"0");
   else if (bound < -1) throw Error("Bound should be >= -1 in setTooSmall() [value is " + bound + "]");

   XRai.updateSaveIcon();
}

XRai.setMaxBelow = function(j,bound,force) {
   // j is associated to a passage
   if (j.passage) {
      var p = j.passage;
      if (debug) XRai.debug("Setting max below " + bound + " for " + p.getPaths() + "\n");
       // Get the intersection
      var start = j.passage.start;
      if (!XRai.isInDocument(start,1)) start = XRai.next(start,1);
      var end = j.passage.end;
      for(var x = start; x && (XRai.isInRange(x,start,end)); x = XRai.next(x,1)) {
         if (debug) XRai.debug("Setting bound for " + XRai.getPath(x) + "\n");
         if (!(x.containers && x.containers.length > 0) && !(x.passages && x.passages.length >
         1))
         XRai.setBound(x.cAssessment,bound,force);
      }
      // Check for first & last element
      var y = XRai.getFirstValidParent(j.passage.start,1);
      if (debug) XRai.debug("Looking at start " + XRai.getPath(y));
      if (y != start && y.passages.length == 1) XRai.setBound(y.cAssessment, bound, force);
      if (debug) XRai.debug("Looking at end " + XRai.getPath(y));
      y = XRai.getFirstValidParent(j.passage.end,1);
      if (y != end && y.passages.length == 1) XRai.setBound(y.cAssessment, bound, force);
      return false;
   }

   // Container or intersection
   var x = j.parentNode;
   if (debug) XRai.debug("Setting max below " + bound + " for " + XRai.getPath(x) + "\n");
   var f = true;
   // Process all contained elements
   if (x.containers) for(var i = 0; i < x.containers.length; i++) {
      var y = x.containers[i];
      if (debug) XRai.debug("=/=> " + XRai.getPath(y) + "\n");
      y = y.assessment ? y.assessment : y.cAssessment;
      XRai.debug("=" + y + "\n");
//       if (b || (bound >=0))
      XRai.setBound(y,bound,force);
      var b = XRai.setMaxBelow(y,bound,force);
      f |= b;
      }

   // Assessment contained in a passage
   if (x.passages || j.getAttribute("type") == "in") {
      if (debug) XRai.debug("We are in a passage\n");
      for(var z = XRai.next(x,1); z && XRai.isIn(z,x); z = XRai.next(z,1)) {
         if (z.cAssessment) {
            XRai.setBound(z.cAssessment,bound,force);
         }
      }
   }
   return f;
}

XRai.nobelow = function(j,bound,force) {
   try { XRai.setMaxBelow(j,bound,force); }
   catch(e) {
      alert("An error occurred, please report the bug: " + XRai.getError(e));
   }
}



XRai.containsPassage = function(x) {
   for(var p = XRai.firstPassage; p; p = p.next) {
      var lca = p.getLCA();
      if (x == lca || XRai.isIn(lca,x)) return true;
   }
   return false;
}


// -*- Assessment creation / deletion is done ONLY with these methods

XRai.newAssessment = function(x,a,passage,saved) {
   var p = document.createElementNS(xrains, xraiatag);
   p.setAttribute("a",a);
   p.minBelow = 0;
   x.insertBefore(p, x.firstChild);
   if (typeof saved == "undefined") {
      p.value = XRai.getAValue(p);
      XRai.changeCount++;
   } else {
      p.saved = saved;
      p.value = saved;
   }
   if (a == "0") XRai.addElementToArray(XRai.toAssess,p);

   if (a > 0) {
      if (passage) XRai.upwardUpdate(passage.getLCA(), 0, a, true, false);
      else XRai.upwardUpdate(x.parentNode, 0, a, true, true);
   }
   return p;
}

XRai.removeAssessment = function(e) {
   if (debug) XRai.debug("Removing assessment for " + (e.passage ? e.passage.getPaths() : XRai.getPath(e.parentNode)) + "\n");
   if (!e.passage) e.parent = e.parentNode;
   if (typeof e.saved != "undefined") {
      if (e.value == e.saved) XRai.changeCount++;
      XRai.assessmentsToRemove.push(e);
   } else {
      XRai.changeCount--;
   }

   var a = e.getAttribute("a");
   if (a == "0") XRai.removeElementFromArray(XRai.toAssess,e);
   if (a > 0)
      if (e.passage) XRai.upwardUpdate(e.passage.getLCA(), a, 0, false, false);
      else XRai.upwardUpdate(e.parentNode, a, 0, false, true);

   if (e.passage) e.passage.assessment = null;
   else (e.parentNode.cAssessment = null);
   e.parentNode.removeChild(e);
}

// Set the value of an assessment tag
XRai.setAssessment = function(e, a, loading) {
   var olda = e.getAttribute("a");
   if (olda != a) {
      if (debug) XRai.debug("Assessing " + (e.passage ? e.passage.getPaths() : XRai.getPath(e.parentNode)) + " to " + a +"\n");

      if (a == "0") XRai.addElementToArray(XRai.toAssess,e);
      else if (olda == "0") XRai.removeElementFromArray(XRai.toAssess,e);

      if (!loading) {
         if (e.passage)
            XRai.addHistory("ASSESS",e.passage.start,e.passage.end);
         else
            XRai.addHistory("ASSESS",e.parentNode,null);
      }
      e.setAttribute("a",a);
      XRai.assessmentChanged(e);
      // Check consistency upward if the exhaustivity decreased from > 0
      if (olda > 0 || a > 0) {
         if (e.passage) XRai.upwardUpdate(e.passage.getLCA(), olda, a, false, false);
         else XRai.upwardUpdate(e.parentNode, olda, a, false, true);
      }
      return true;
   }
   return false;
}

// ========== @p6
// ========== Load & save
// ==========

XRai.updateStatusIcon = function() {
   if (!write_access) return;
   var img = document.getElementById("switchImg");
   var divinex = document.getElementById("inex");
   var spanh = document.getElementById("highlight");
   if (!highlight_only) {
    if (docStatus > 0) {
      img.src = base_url + "/img/mode_assess.png";
      divinex.setAttribute("mode","assess");
      spanh.style.display = "none";
    } else {
      img.src = base_url + "/img/mode_highlight.png";
      divinex.setAttribute("mode","highlight");
      spanh.style.display = null;
    }
   }
}

function setSaving(hidden) {
   document.getElementById("saving_div").style.visibility = hidden ? "hidden" : "visible";
}

function setSavingMessage(txt) {
   var saving_message = document.getElementById("saving_message");
   saving_message.replaceChild(document.createTextNode(txt), saving_message.firstChild);
}

XRai.getAValue = function(a) {
   var nv = parseInt(a.getAttribute("a"));
   if (a.passage) if (!a.passage.validated) nv = null; else if (!a.passage.active) nv = -nv - 1;
   return nv;
}

XRai.assessmentChanged = function(a) {
   // Compute the new value
   var nv = XRai.getAValue(a);
   if (nv != a.value) {
      if (typeof a.saved != "undefined") {
         if (a.saved == nv) XRai.changeCount--; else XRai.changeCount++;
      }
      a.value = nv;
   }
}

XRai.hasChanged = function() {
   return XRai.changeCount > 0 || (XRai.originalBEP != XRai.BEPElement.parentNode) || (oldDocStatus != docStatus);
}

XRai.isConsistent = function() {
  if (highlight_only) return !((XRai.BEPElement.parentNode != null) ^ XRai.hasRelevant());
  return (docStatus == 1 && XRai.toAssess.length == 0);
}

XRai.updateSaveIcon = function() {
   if (!write_access) return;
   var save = document.getElementById("save");
   if (XRai.hasChanged()) { save.src = baseurl + "img/filesave.png"; save.setAttribute("title","Save current assessments (" + XRai.changeCount + " changes)");  }
   else { save.src =  baseurl + "img/filenosave.png"; save.setAttribute("title","Nothing to save"); }

   if (!highlight_only) {
        var x = document.getElementById("UnknownA");
        x.replaceChild(document.createTextNode(XRai.toAssess.length), x.firstChild);
   }

   // Update the validation
   if (highlight_only) {
      if (docStatus == 2) XRai.setFinished(2);
      else if (XRai.isConsistent()) XRai.setFinished(1);
      else XRai.setFinished(0);
   } else if (docStatus > 0) {
      if (docStatus == 2 && XRai.toAssess.length > 0) docStatus = 1;
      if (docStatus == 1 && XRai.toAssess.length == 0) { XRai.setFinished(1); }
      else if (docStatus == 2) XRai.setFinished(2);
      else XRai.setFinished(0);
   } else XRai.setFinished(0);
}

// Called when the user clicked on "finish"
XRai.onFinishClick = function() {
   var t = document.getElementById("finishImg");
   if (highlight_only) {
     if (docStatus == 2 ) docStatus = 0; 
     else if (XRai.isConsistent()) docStatus = 2;
   } else {
     if (XRai.isConsistent()) { docStatus = 2; }
     else if (docStatus == 2) { docStatus = 1; }
   }
   XRai.updateSaveIcon();
}

XRai.setFinished = function(v) {
   v = parseInt(v);
   var t = document.getElementById("finishImg");
   switch(v) {
      case 0:
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


// Called when the assessments were saved
XRai.saved = function(b) {
   if (XRai.saveForm) {
      XRai.saveForm = null;
   }
   if (b) {
      for(var i = 0; i < XRai.toSave.length; i++)
         XRai.toSave[i].saved = XRai.getAValue(XRai.toSave[i]);
      XRai.toSave = null;
      XRai.assessmentsToRemove = new Array();
      XRai.history = new Array();
      XRai.changeCount = 0;
      XRai.originalBEP = XRai.BEPElement.parentNode; 
      oldDocStatus = docStatus;
      for(var p = XRai.firstPassage; p; p = p.next) p.saved = p.validated ? Passage.VALIDATED : Passage.UNVALIDATED;
      for(var p = XRai.firstOldPassage; p; p = p.next) p.saved = Passage.OLDVALIDATED;

      XRai.updateSaveIcon();
   }
   document.getElementById('saving_div').style.visibility = 'hidden';
   if (XRai.onsaved) XRai.onsaved();
}

function createHiddenInput(name,value,doc) {
   var x = (doc ? doc : document).createElement("input");
   x.setAttribute("type","hidden");
   x.setAttribute("name",name);
   x.setAttribute("value",value);
   return x;
}


// Called when the user wants to save the document
XRai.save = function() {
   if (debug) XRai.debug("Save: checking");

   if (!XRai.hasChanged()) {
      Message.show("notice","Nothing to save");
      return;
   }

   if (XRai.noSave) {
      Message.show("error","Assessments cannot be saved (a *major* error occured before)\n",-1);
      return;
   }

   if (!XRai.loaded) {
      Message.show("error","Assessments cannot be saved (document not completly loaded)\n",-1);
      return;
   }

   if (XRai.saveForm != null) {
      Message.show("warning","Another save of assessments is being processed");
      return;
   }

   if (debug) XRai.debug("Save: initialisation");

   var saving_div = document.getElementById("saving_div");
   var saving_message = document.getElementById("saving_message");
   var saving_iframe = document.getElementById("assessing");
   if (!saving_iframe) {
      alert("Hmmm. Bug! Can't retrieve the iframe 'assessing' for assessing");
      return;
   }
      
   var saving_doc = saving_iframe.contentDocument;
   var bdy = saving_doc ? saving_doc.getElementsByTagName("body") : null;

  if (!bdy || !bdy[0]) {
      Message.show("error","Assessments cannot be saved (cannot find the assessment frame)\n",-1);
      return;
   }
   
   // Prepare the frame

   if (debug) XRai.debug("Save: preparing the assessments");
   setSavingMessage("Preparing assessments");
   saving_div.style.visibility = "visible";

  // Static information
   XRai.saveForm = saving_doc.createElement("form");
   XRai.saveForm.style.display = "none";
//    XRai.saveForm.setAttribute("target","xrai-assessing");
   XRai.saveForm.setAttribute("action",base_url + "/assess.php");
   XRai.saveForm.setAttribute("method","post");
   XRai.saveForm.appendChild(createHiddenInput("id_pool",id_pool, saving_doc));
   XRai.saveForm.appendChild(createHiddenInput("collection",xrai_collection, saving_doc));
   XRai.saveForm.appendChild(createHiddenInput("file",xrai_file, saving_doc));
   XRai.saveForm.appendChild(createHiddenInput("aversion",aversion, saving_doc));
   XRai.saveForm.appendChild(createHiddenInput("docstatus",docStatus,saving_doc));
   

   // Add history
   XRai.addHistory("SAVE");
   for(var i = 0; i < XRai.history.length; i++) {
      var x = XRai.history[i];
      XRai.saveForm.appendChild(createHiddenInput("hist[]",x[0]+","+x[1]+","+ (x[2] ? XRai.getPath(x[2]) : "") + "," + (x[3] ? XRai.getPath(x[3]) : ""), saving_doc));
   }

   // Add assessments
   XRai.toSave = new Array();
   var xraij = document.getElementsByTagNameNS(xrains,xraiatag);
   for(var n = 0; n < xraij.length; n++) {
      var res = xraij[n];
      res.value = XRai.getAValue(res);
      if ((typeof res.saved != "undefined") && (res.saved == res.value)) {
         if (debug) XRai.debug("Skipping " + res + "\n");
      } else {
         XRai.toSave.push(res);
         var s = (typeof res.saved != "undefined" ? 1 : 0) + "," + XRai.getAValue(res) + ",";
         if (res.passage) s += XRai.getPath(res.passage.start) + "," + XRai.getPath(res.passage.end);
         else s += XRai.getPath(res.parentNode) + ",";
         if (debug) XRai.debug("Adding " + s + " (" + res.saved + "/" + res.value + ")\n");
         XRai.saveForm.appendChild(createHiddenInput("a[]",s,saving_doc));
      }
      }

  // Add passages
  for(var p = XRai.firstPassage; p; p = p.next) {
      if (p.saved != Passage.VALIDATED) {
         var s = (typeof p.saved != "undefined" ? 1 : 0) + ","
                  + (p.validated ?  Passage.VALIDATED : Passage.UNVALIDATED) + ",";
         s += XRai.getPath(p.start) + "," + XRai.getPath(p.end);
         if (debug) XRai.debug("Adding " + s + " (" + p.getPaths() + ")\n");
         XRai.saveForm.appendChild(createHiddenInput("a[]",s,saving_doc));
      }
   }
   
   // Add BEP
   XRai.saveForm.appendChild(createHiddenInput("BEP",XRai.getPath(XRai.BEPElement.parentNode),saving_doc));

   // Is the document relevant?
   XRai.saveForm.appendChild(createHiddenInput("hasrelevant", XRai.firstPassage ? 1 : 0, saving_doc));

   
   // Add passages
   for(var p = XRai.firstOldPassage; p; p = p.next) {
      if (p.saved != Passage.OLDVALIDATED) {
         var s = (typeof p.saved != "undefined" ? 1 : 0) + "," + Passage.OLDVALIDATED + ",";
         s += XRai.getPath(p.start) + "," + XRai.getPath(p.end);
         if (debug) XRai.debug("Adding " + s + " (" + p.getPaths() + ")\n");
         XRai.saveForm.appendChild(createHiddenInput("a[]",s,saving_doc));
      }
   }

  // Add to remove
   for(var i = 0; i < XRai.assessmentsToRemove.length; i++) {
      var s = "";
      res = XRai.assessmentsToRemove[i];
      if (res.isPassage) s += XRai.getPath(res.start) + "," + XRai.getPath(res.end);
      else s += XRai.getPath(res.parent) + ",";
      XRai.saveForm.appendChild(createHiddenInput("r[]",s,saving_doc));
   }

  // Submit
   bdy[0].appendChild(XRai.saveForm);
   if (debug) XRai.debug("Save: connecting to server");
   setSavingMessage("Connecting to server...");
   XRai.saveForm.submit();
}


function isInvalidTagName(name) {
   return name.match(/^([a-zA-Z][^:]*)(:[a-zA-Z][^:]*)?$/) == null;
}



XRai.stepre = /^([^\/\[\]]+)\[(\d+)\]$/;

XRai.resolvePathStep = function(child, stack, i) {
   var s = XRai.stepre.exec(stack[i]);
   if (debug) XRai.debug("Resolving " + s[1] + ", " + s[2] +  ", fs=" + (child.nodeType != Node.TEXT_NODE ? XRai.getPath(child) : XRai.getPath(child.parentNode) + "/text()[1]" ) + "\n");
   var rank = s[2];
   var isText = s[1] == "text()";
   var isFirstXRaiS = true;
   for(; child && rank; child = XRai.nextSibling(child)) {
      var name = XRai.getTagName(child);
      if (isText) {
         if (name == "xrai:s" && isFirstXRaiS) {
            isFirstXRaiS = false;
               rank--;
         } else if (child.nodeType == Node.TEXT_NODE) {
               if (s[2] != "1") {
                  XRai.debug("Searching for text()[" + s[2] + "] and node is pure PCDATA");
                  return null;
               }
               rank--;
         } else {
            isFirstXRaiS = true;
         }
      } else if (name == s[1]) rank--;
      
      if (!rank) break;
   }
   if (!child) {
      if (debug) XRai.debug("Step " + stack[i] + " not found!\n");
      return null;
   }
   i++;
   if (stack.length > i) return XRai.resolvePathStep(XRai.firstChild(child),stack,i);
   return child;
}
         
XRai.resolvePath = function(path) {
   if (path == "" || path == null || path == "null") return null;
   var x;
   if (XRai.xpe) {
      var matches = path.split("/");
      var s = "";
      var m;
      var searchText = null;
      for(var i = 1; i < matches.length; i++)
      if (m = matches[i].match(/^(?!xrai:)(.*:.*)(\[.+)$/)) 
               s += "/xraic:xrai_itag[@xrai_tagname=\"" + m[1] + "\"]" + m[2];
      else if (matches[i].indexOf("xrai:") == 0) s += "/" + matches[i];
      else if (m = matches[i].match(/^text\(\)\[(\d+)\]$/)) searchText = m[1];         
      else s += "/xraic:" + matches[i];
            
      path = "." + s; // path.replace(/\/(?!xrai:)/g,"/xraic:");
      if (debug) XRai.debug("Evaluating " + path + (searchText ? " with text()[" + searchText + "]" : "")  + "\n");
      try {
         x = XRai.xpe.evaluate(path, XRai.getRoot().parentNode, XRai.nsResolver, 0, null);
         x = x.iterateNext();
         if (x && searchText) x = XRai.resolvePathStep(x.firstChild, new Array("text()[" + searchText + "]"), 0);
      } catch(e) {
         XRai.debug("Caught exception " + e + "\n");
         return null;
      }
   } else  {
      // No XPath resolution in browser: do it ourselves
      stack = path.split("/");
      x = XRai.resolvePathStep(XRai.getRoot(), stack, 1);
   }
   
   return x;    
   
}
// Loading
XRaiLoad = function() {

   this.loadErrors = 0;
   this.loadWarnings = 0;
   this.errorCode = 0;
   
   this.BEP_ERROR = 1;
   this.SUPPORT_ELEMENT_ERROR = 2;
   this.ASSESSMENT_ERROR = 4;
   
   this.toadd = new Array();

   this.add = function(start,end,a) {
      try {
         if (debug) XRai.debug("[LOADING" + (end != "" ? "*" : "") + "] " + a + " : "  + start + " - " + end + "\n");
         var eStart = XRai.resolvePath(start);
         var eEnd = XRai.resolvePath(end);
         
         if (!eStart || (end != "" && !eEnd)) {
            if (debug) XRai.debug("[LDERROR] " + eStart + "/" + eEnd + "\n");
            this.loadErrors++;
            this.errorCode |= this.ASSESSMENT_ERROR;
         } else {
            if (eEnd) {
               try {
                  var p = new Passage(eStart, eEnd, a);
                  if ((p.start != eStart) || (p.end != eEnd)) {
                     XRai.error("[LDERROR] Loaded passage was not normalised!\n");
                     this.loadErrors++;
                     this.errorCode |= this.ASSESSMENT_ERROR;
                  }
               } catch(e) {
                  this.loadErrors++;
                  if (debug) XRai.debug("[LDERROR] " + e + "\n");
               }
            } else {
               this.toadd.push(new Array(eStart, a));
            }
         }
      } catch(e) {
         if (debug) XRai.debug("Error while loading assessments: " + XRai.getError(e) + "\n");
         this.loadErrors++;
         this.errorCode |= this.ASSESSMENT_ERROR;
      }
   }

   // Adds a support element
   // Can be a passage
   this.addSupport = function(startpath, endpath) {
      if (debug) XRai.debug("[LOAD] Loading support element: " + startpath + " => " + endpath + "\n");
      var startPath = XRai.resolvePath(startpath);
      var endPath =  XRai.resolvePath(endpath);
      
      if (!startPath || !endPath) {
         if (debug) XRai.debug("Error: " + endPath + "\n");
         this.loadWarnings++;
         this.errorCode |= this.SUPPORT_ELEMENT_ERROR;
      } else {
         if (startPath.nodeType == Node.TEXT_NODE) startPath = startPath.parentNode;
         if (endPath.nodeType == Node.TEXT_NODE) endPath = startPath.parentNode;         
         var p = XRai.normalisePassage(startPath, endPath);
         for(var e = p.x; e != null; e = XRai.nextElementTo(e,p.y)) {        
            try { e.setAttribute("support",1); }
            catch(error) {
               this.loadWarnings++;
               this.errorCode |= this.SUPPORT_ELEMENT_ERROR;
               if (debug) XRai.debug("[ERROR] (support element set attribute) " + e + "\n");
            }
         }
      }
   }
		 
   this.setBEP = function(path) {
      if (path == "" || path == null || path == "null") return;
      var ePath = XRai.resolvePath(path);
      if (!ePath) {
         if (debug) XRai.debug("Error (BEP): path not resolved " + ePath  + "/" + path + "\n");
         this.loadErrors++;
         this.errorCode |= this.BEP_ERROR;
      } else {
         XRai.originalBEP = ePath;
         XRai.setBEP(ePath);
      }
   }

   this.getErrorMessage = function(html) {  
         var s = "";
         var br = html ? "<br/>" : "\n";
         if (this.errorCode & this.ASSESSMENT_ERROR) s += br + "Error while loading assessments";
         if (this.errorCode & this.BEP_ERROR) s += br + "Error while loading BEP";
         if (this.errorCode & this.SUPPORT_ELEMENT_ERROR) s += br + "Error while loading support elements";
         return s;
   }
   
   this.showError = function() {
         alert("Error while loading assessment context. You MUST NOT assess this file (or you can try to erase all the assessments by clicking on the trash icon in the status bar)." + this.getErrorMessage());
         XRai.noSave = true;
   }
   
   this.end = function() {
      var toomuch = 0; // number of extra assessments (due to an old bug)

      if (this.loadErrors != 0) {
         this.showError();
      }
      try {
         // Update containers
         for(var p = XRai.firstPassage; p; p = p.next)
            if (p.validated) p.validate();
         for(var p = XRai.firstOldPassage; p; p = p.next)
            if (p.validated) p.validate();
            else throw Error("An element in the old list should be validated!");

         for(var i = 0; i < this.toadd.length; i++) {
            if (!this.toadd[i][0].cAssessment) {
               toomuch++;
               XRai.changeCount++;
               var p = document.createElementNS(xrains, xraiatag);
               p.setAttribute("a",this.toadd[i][1]);
               p.saved = this.toadd[i][1];
               p.parent = this.toadd[i][0];
               XRai.assessmentsToRemove.push(p);
//                throw new Error(XRai.getPath(this.toadd[i][0]) + " has not to be assessed, but an assessment has been saved");
            } else {
               XRai.setAssessment(this.toadd[i][0].cAssessment,this.toadd[i][1],true);
               this.toadd[i][0].cAssessment.saved = this.toadd[i][1];
               XRai.changeCount--;
            }
         }

         if (toomuch > 0)
            Message.show("warning","Some assessments were in non highlighted passages and have been removed",-1);

         if (XRai.firstOldPassage) docStatus = 0;
         XRai.updateSaveIcon();
         XRai.updateStatusIcon();
         if (debug) XRai.dumpPassages();
         if (this.loadWarnings > 0) 
            Message.show("warning","There were some warnings while loading the documents, but you can still assess this document: " + this.getErrorMessage(),-1);
      } catch(e) {
         if (debug) XRai.debug("Error while loading assessments: " + XRai.getError(e) + "\n");
         this.showError();
         throw e;
      }
   }

}

function check_move(way) {
   if (docStatus >= 1) {
      if (XRai.toAssess.length == 0) {
         if (docStatus == 2) Message.show("notice","No more assessments in this view; click on the arrow while pressing shift to jump to the previous view to assess.");
         else Message.show("notice","No more elements to assess in this view");
         return false;
      }
   } else {
      Message.show("notice","Arrows only work in assessing mode");
      return false;
   }
   return true;
}

function todo_previous(jump) {
   if (jump) {
      XRai.gotoLocation(goto_url + "&next=0");
   } else if (check_move("previous")) {
      if (todo_index > 0) todo_index = (todo_index - 1) % XRai.toAssess.length;
      else todo_index = XRai.toAssess.length - 1;
      show_focus(XRai.toAssess[todo_index]);
   }
}

function todo_next(jump) {
   if (jump) {
      XRai.gotoLocation(goto_url + "&next=1");
   } else if (check_move("next")) {
      todo_index = (todo_index + 1) % XRai.toAssess.length;
      show_focus(XRai.toAssess[todo_index]);
   }
}

XRai.saveAndGo = function(previous) {
   if (!confirm("Are you sure that you want to validate this file (every non highlighted element will be assessed as non relevant)")) return;
   if (!XRai.switchToAssess()) return;
   XRai.updateStatusIcon();
   XRai.updateSaveIcon();
   if (docStatus != 2) XRai.onFinishClick();
   XRai.updateStatusIcon();
   XRai.updateSaveIcon();
   if (docStatus != 2) Message.show("warning","Document cannot be validated");
   else {
      if (previous) XRai.onsaved = function() { todo_previous(true); }
      else XRai.onsaved = function() { todo_previous(false); }
      if (XRai.hasChanged()) XRai.save();
      else XRai.onsaved();
   }
}

XRai.erase = function() {
   var r = window.prompt("Type 'erase' in order to confirm your order");
   if (r != "erase") Message.show("notice","Command not confirmed");
   else {
      var d = document.getElementById("erasing");
      setSavingMessage("Connecting to server...");
      setSaving(false);
      d.src = base_url + "/iframe/trash.php?file=" +  xrai_file + "&idpool=" + id_pool + "&collection=" + xrai_collection + "&time=" + XRai.getTimeString();
   }
}

XRai.hasFocus = true;

XRai.onfocus = function(event) {
 if (XRai.hasFocus) return;
 XRai.hasFocus = true;
 XRai.addHistory("FOCUS");
}
XRai.onblur= function(event) {
 if (!XRai.hasFocus) return;
 XRai.hasFocus = false;
 XRai.addHistory("BLUR");
}

XRai.initFunctions = Array();

// Called when the XML is loaded
XRai.init = function() {
   if (XRai.onmousedown) {
      XRai.getRoot().addEventListener("mousedown",XRai.onmousedown,false);
      XRai.getRoot().addEventListener("mouseup",XRai.onmouseup,false);
   }
   if (XRai.numberNodes) XRai.numberNodes();

   XRai.loaded = true;
   XRai.addHistory("LOAD");
   window.addEventListener("blur", XRai.onblur, true);
   window.addEventListener("focus", XRai.onfocus, true);
   for(var i = 0; i < XRai.initFunctions.length; i++)
      XRai.initFunctions[i]();
      
      
   // Init XPath resolution variables
   if (document.implementation.hasFeature("XPath", "3.0")) {
      XRai.xpe = document; //new XPathEvaluator();
      XRai.nsResolver = XRai.xpe.createNSResolver(XRai.getRoot());
   }
   
   if (debug) {
      XRai.debug("\n\nPath resolution\n");
      if (XRai.xpe) XRai.debug("NS resolver: xraic = " + XRai.nsResolver.lookupNamespaceURI("xraic") + "; xrai = " + XRai.nsResolver.lookupNamespaceURI("xrai") + "\n");
   }

   XRai.debug("Document loaded");
}




// =================== @p7 BEP handling

XRai.BEPMode = false;
XRai.highlightedBEP = null;
XRai.originalBEP = null;

XRai.BEPElement = document.createElementNS(htmlns, "img");
XRai.BEPElement.setAttribute("src", base_url + "/img/bep.png");
XRai.BEPElement.setAttribute("title","Best Entry Point");
XRai.BEPElement.setAttribute("alt","[BEP]");
XRai.BEPElement.setAttribute("style","border: 1px solid red;");

// This handler is called when the mouse moves
XRai.bep_mouseover = function(event) {
   var x = event.target;
   if (x.nodeType == Node.TEXT_NODE) x = x.parentNode;
   while (x && !XRai.isInDocument(x)) x = x.parentNode;
   if (!x) return;
   if (XRai.highlightedBEP) XRai.highlightedBEP.removeAttribute("xrai_BEP");
   x.setAttribute("xrai_BEP",1);
   XRai.highlightedBEP = x;
}


XRai.bep_mouseout = function(event) {
   if (XRai.highlightedBEP) {
   	XRai.highlightedBEP.removeAttribute("xrai_BEP");
	XRai.highlightedBEP = null;
   }
}

// The user clicked
XRai.bep_mouseup = function(event) {
   XRai.debug("BEP: mouse up");
   var x = event.target;
   if (x.nodeType == Node.TEXT_NODE) x = x.parentNode;
   while (x && !XRai.isInDocument(x)) x = x.parentNode;
   if (!x) return;

   XRai.setBEP(x, true);
   XRai.toggleBEPMode();
}

XRai.setBEP = function(x, addToHistory) {
   var p = XRai.BEPElement.parentNode;
   if (p) p.removeChild(XRai.BEPElement);
   if (x) x.insertBefore(XRai.BEPElement, x.firstChild);
   XRai.updateSaveIcon();
   if (addToHistory) XRai.addHistory("BEP", x);
}

// This function is called when the judge clicks on
// the "BEP button"
XRai.toggleBEPMode = function(event) {
   if (!XRai.loaded || docStatus == 2) return;
   
   if (!XRai.BEPMode) {
      XRai.addHistory("BEP-ON");
      XRai.getRoot().addEventListener("mouseover",XRai.bep_mouseover,false);
      XRai.getRoot().addEventListener("mouseout",XRai.bep_mouseout,false);
      XRai.getRoot().addEventListener("mouseup",XRai.bep_mouseup,false);
      XRai.getRoot().parentNode.style.cursor = "crosshair !important";
   } else {
      XRai.addHistory("BEP-OFF");
      XRai.getRoot().removeEventListener("mouseover",XRai.bep_mouseover,false);
      XRai.getRoot().removeEventListener("mouseout",XRai.bep_mouseout,false);
      XRai.getRoot().removeEventListener("mouseup",XRai.bep_mouseup,false);
      XRai.getRoot().parentNode.style.cursor = "";
      XRai.bep_mouseout();
   }
   
   XRai.BEPMode = !XRai.BEPMode;
   XRai.debug("BEP mode is " + XRai.BEPMode);
}


XRai.xpathMove = function() {
  var x = prompt("Enter the XPath"); 
  if (x) {
     var y = XRai.resolvePath(x);
     if (y) show_focus(y);
  }
}
