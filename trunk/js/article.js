// Javascript code
// Article view javascript code
// (c) B. Piwowarski, 2004-2005

// Parts (@p__ to search)
// p1: Misc
// p2: Events
// p3: navigation
// p4: highlighting part

var XRai = new Object(); // used for namespace


XRai.lastX = 0;
XRai.lastY = 0,
XRai.mode = 0; // 0 for highlighting -> 1 for assessing

XRai.newPassages = new Array();
XRai.erasePassages = new Array();
XRai.changedPassages = new Array();

// ==========  @p1
// ========== Misc
// ==========


// *** Remove an element in an array
// A: no duplicate in the array
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
      else return x.ptag;
   return null;
}
XRai.onmouseover = function(event) {
   if (XRai.mode == 1) {
//       var x = XRai.findHighlighted(XRai.isInDocument(event.target) ? event.target : XRai.previous(event.target));
   }
};

XRai.onclick = function(event) {
   if (XRai.mode == 1) {
      var x = XRai.findHighlighted(XRai.isInDocument(event.target) ? event.target : XRai.previous(event.target));
      if (x) XRai.showEvalPanel(x,event.pageX,event.pageY);
  }
}

XRai.mouseout = function(event) {
}

XRai.keypressed = function (event) {
  var N = !event.shiftKey && !event.ctrlKey;
  var S = event.shiftKey && !event.ctrlKey;
  var C = !event.shiftKey && event.ctrlKey;
  var SC = event.shiftKey && event.ctrlKey;

  if (N && event.which == 104) XRai.highlight();
  else if (N && event.which == 117) XRai.unhighlight();
  else {
     if (debug) window.dump("Key pressed: charchode" + event.charCode
        + ", keycode=" + event.keyCode
      + ", which=" + event.which + ", shiftKey=" + event.shiftKey + ", ctrlKey=" + event.ctrlKey
      + ", x= " + event.pageX + "\n");
   return collection_keypress(event);
  }

  return false;
}


// Check that everything is saved before allowing the user to go out of this view
XRai.beforeunload = function(event) {
}


XRai.switchMode = function() {
     var img = document.getElementById("switchImg");
     if (XRai.mode == 0) {
      img.src = base_url + "/img/unhighlight.png";
     } else {
      img.src = base_url + "/img/highlight.png";
     }
     XRai.mode = 1 - XRai.mode;
}





// ========== @p3
// ========== Navigation
// ==========

/*
   Returns true if x is in y
*/
XRai.isIn = function(x, y) {
   return x.compareDocumentPosition(y) & Node.DOCUMENT_POSITION_CONTAINS;
}
/*
   Returns true if x is in y
*/
XRai.isBefore = function(x, y) {
   return x.compareDocumentPosition(y) & Node.DOCUMENT_POSITION_FOLLOWING;
}
XRai.isAfter = function(x, y) {
   return x.compareDocumentPosition(y) & Node.DOCUMENT_POSITION_PRECEDING;
}

XRai.isBetween = function(x,a,b) {
//   window.dump(x.compareDocumentPosition(a) + "&" +  Node.DOCUMENT_POSITION_PRECEDING + " && " + x.compareDocumentPosition(b) + "&" + Node.DOCUMENT_POSITION_FOLLOWING + " - " + (x==a) + " / " + (x==b) + "\n");
   return (x==a) || (x==b) ||
       ((x.compareDocumentPosition(a) & Node.DOCUMENT_POSITION_PRECEDING) && (x.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING));
}


// Get the true XML container of the current element
XRai.getContainer = function(x) {
   while (x && x.namespaceURI != documentns) x = x.parentNode;
   return x;
}

XRai.isInDocument = function(x) {
   return x.namespaceURI == documentns;
}


XRai.parent = function(e) {
   var x = e.parentNode;
   if (!x || x.namespaceURI != documentns) return null;
   return x;
}

XRai.nextSibling = function(e) {
   e = e.nextSibling;
   while (e && (e.nodeType != Node.ELEMENT_NODE || e.namespaceURI != documentns)) e = e.nextSibling;
   return e;
}

XRai.firstChild = function(e) {
   e = e.firstChild;
   while (e && (e.nodeType != Node.ELEMENT_NODE || e.namespaceURI != documentns)) e = e.nextSibling;
   return e;
}

XRai.lastChild = function(e) {
   e = e.lastChild;
   while (e && (e.nodeType != Node.ELEMENT_NODE || e.namespaceURI != documentns)) e = e.previousSibling;
   return e;
}

XRai.previousSibling = function(e) {
   e = e.previousSibling;
   while (e && (e.nodeType != Node.ELEMENT_NODE || e.namespaceURI != documentns)) e = e.previousSibling;
   return e;
}


/** Return the previous element (document order): previous sibling or parent */
XRai.previous = function(x) {
   var y;
   if (y= XRai.previousSibling(x)) return y;
   return XRai.parent(x);
}

/** Return the next element: first child, next sibling or the first ancestor next sibling */
XRai.next = function(x) {
   var y;
   if (y= XRai.firstChild(x)) return y;
   while (x != null) {
      if (y = XRai.nextSibling(x)) return y;
      x = XRai.parent(x);
   }
   return null;
}

/** Return the previous element (document order): previous sibling or ancestor first previous sibling*/
XRai.noDirectPrevious = function(x) {
   var y;
   if (y= XRai.previousSibling(x)) return y;
   while (x != null) {
      if (y = XRai.previousSibling(x)) return y;
      x = XRai.parent(x);
   }
   return null;
}

/** Return the no direct next element:  next sibling or the first ancestor next sibling */
XRai.noDirectNext = function(x) {
   var y;
   while (x != null) {
      if (y = XRai.nextSibling(x)) return y;
      x = XRai.parent(x);
   }
   return null;
}

/** Returns the next XML node leaf after x */
XRai.nextLeaf = function(x) {
   var y = XRai.nextSibling(x);
   while (y == null && x != null) {
      x = XRai.parent(x);
      if (x) y = XRai.nextSibling(x);
   }
   if (y == null) return null;

   var z = y;
   while (z != null) { y = z; z = XRai.firstChild(y); }
   return y;
}

XRai.nextElementTo = function(x,y) {
   if (x == y) return null;
   if (y == null) return XRai.noDirectNext(x);
   var z = null;
   if (!XRai.isIn(y,x)) {
      while (x && z == null) {
         z = XRai.nextSibling(x);
         if (z == null) x = XRai.parent(x);
//          if (debug) window.dump("Current x is = " + XRai.getPath(x) + " / z is " + XRai.getPath(z) + "\n");
      }
      x = z;
   }
   while (x != null && XRai.isIn(y,x)) {
//       if (debug) window.dump("  Loop " + XRai.getPath(y) + " is in " + XRai.getPath(x) + "\n");
      x = XRai.firstChild(x);
   }
   return x;
}

XRai.previousElementTo = function(x,y) {
   if (x == y) return null;
   if (y == null) return XRai.noDirectPrevious(x);
   if (!XRai.isIn(x,y)) {
      var z = XRai.noDirectPrevious(x);
      if (XRai.isIn(y,z)) return XRai.lastChild(z);
   }
   return XRai.previousSibling(x);
}


XRai.getPath = function(e) {
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
}


XRai.getPassagePaths = function(e) {
   if (!e) return "<>";
   return "<" + XRai.getPath(e.parentNode) + (e.lastElement ? "," + XRai.getPath(e.lastElement) : "") + ">";
}

XRai.root = null;
XRai.getRoot = function() {
   if (XRai.root == null)
      for(XRai.root = document.getElementById("inex").firstChild;
            XRai.root && XRai.root.nodeType != Node.ELEMENT_NODE; XRai.root = XRai.root.nextSibling) {}
   return XRai.root;
}






// ========== @p4
// ========== Highlighting
// ==========

// Passage information is contained in a passage element (xraiptag variable)
// the parent node is the start of the passage
// lastElement points on the last element of the passage
// nextPassage points to the next passage (document order)
// previousPassage points to the previous passage

// XML nodes which are within a passage are marked with attribute marked=1


// The first highlighted passage in document order
XRai.firstPassage = null; 


XRai.getRange = function(range) {
}

XRai.removePassage = function(x) {
   if (x.previousPassage) x.previousPassage.nextPassage = x.nextPassage;
   else {
      if (XRai.firstPassage != x)
         throw new Error("The passage " + XRai.getPassagePaths(x) + "has no previous passage but is not the first passage (" + XRai.getPassagePaths(XRai.firstPassage) + ")");
      XRai.firstPassage = x.nextPassage;
      if (x.nextPassage) XRai.firstPassage.previousPassage = null;
   }
   if (x.parentNode) x.parentNode.removeChild(x);
}

XRai.clearPassage = function(p,t) {
   for(x = p.parentNode; x != null; x = XRai.nextElementTo(x,p.lastElement)) {
      x.removeAttribute("marked");
      if (debug) window.dump("UNHIGH " + XRai.getPath(x) + "\n");
      if (t) t.push(x);
   }
}

XRai.highlightPassage = function(p) {
   for(x = p.parentNode; x != null; x = XRai.nextElementTo(x,p.lastElement)) {
      x.setAttribute("marked",1);
      x.ptag = p;
   }
}

XRai.addPassage = function(previous, x, y) {
   p = document.createElementNS(xrains, xraiptag);
   p.setAttribute("a","U");
   p.lastElement = y;
   x.insertBefore(p, x.firstChild);
   if (previous) {
      p.nextPassage = previous.nextPassage;
      p.previousPassage = previous;
      previous.nextPassage = p;
   } else {
      p.nextPassage = XRai.firstPassage;
      XRai.firstPassage = p;
      if (p.nextPassage) p.nextPassage.previousPassage = p;
   }
   return p;
}

XRai.getSelection = function() {
   if (!writeAccess) return null;
   var selection = window.getSelection();
   var range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
   selection.collapseToStart();

   if (range == null || range.collapsed) {
      Message.show("warning","No selected elements");
      return;
   }

   // Get the start and the end of the container
   var x = XRai.isInDocument(range.startContainer) ? range.startContainer :XRai.previous(range.startContainer);
   var y = XRai.isInDocument(range.endContainer) ? range.endContainer : XRai.previous(range.endContainer) ;
   while (XRai.isIn(y,x)) x = XRai.firstChild(x);
   return { x: x, y: y};
}

XRai.unhighlight = function() {
   if (XRai.mode != 0) return;
   var toremove = new Array();
   var toadd = new Array();
   try {
      var sel = XRai.getSelection();
      if (sel == null) return;
      var x = sel.x;
      var y = sel.y;
      var previous = null;
      // Find the first passage 
      z = XRai.firstPassage;
      while (z && XRai.isAfter(x,z.lastElement)) {
         previous = z; z = z.nextPassage;
      }
      
      // Check overlap with z/x
      if (z && XRai.isAfter(x,z.parentNode)) {
         // Break the node
         toremove.push(z);
         toadd.push(new Array(previous, z.parentNode, XRai.previousElementTo(x,z.parentNode)));
         if (debug)
            window.dump("Inter(" + XRai.getPassagePaths(z) + " with " + XRai.getPath(x) + ") => "
               + XRai.getPath(XRai.previousElementTo(x,z.parentNode)) + "\n");
         
      }
   
      // While y is after z end
      for(; z && XRai.isAfter(y,z.lastElement); z = z.nextPassage) {
         toremove.push(z);
      }
   
      // Check overlap z/y
      if (z && XRai.isAfter(z.lastElement,y)) {
         toremove.push(z);
         toadd.push(new Array(z,XRai.nextElementTo(y,z.lastElement),z.lastElement));
      }
   
      try {
         for(var i = 0; i < toadd.length; i++) toadd[i] = XRai.addPassage(toadd[i][0], toadd[i][1], toadd[i][2]);
         for(var i = 0; i < toremove.length; i++) {
            XRai.clearPassage(toremove[i]);
            XRai.removePassage(toremove[i]);
         }
         for(var i = 0; i < toadd.length; i++) XRai.highlightPassage(toadd[i]);
      } catch (error) {
         alert("Unrecoverable error: you should stop assessing WITHOUT SAVING and fill a bug report\n\n" + error);
      }
   } catch(error) {
         alert("Recoverable error: you should stop assessing, save your assessments and fill a bug report\n\n" + error);
   }
}


XRai.highlight = function() {
   if (XRai.mode != 0) return;
   try {

      var p = null;
   
      var highlighted = new Array();
      var unhighlighted = new Array();
      var toremove = new Array();
   
      var previous = null;
      var y = null;
      var z = null;
      var sel = XRai.getSelection();
      if (sel == null) return;
      var x = sel.x;
      y = sel.y;
      
      if (debug) {
         window.dump("\nHIGHLIGHTING START\n");
         window.dump(XRai.getPath(x) + " -> " + XRai.getPath(y) + "\n");
      }
   
      // (1) Find the previous highlighted passage (can be within)
      z = XRai.firstPassage;
      while (z && (XRai.nextElementTo(z.lastElement,x) != x) && XRai.isAfter(x,z.lastElement)) {
         previous = z; z = z.nextPassage;
      }
      if (debug) {
         window.dump("Next conflicting passage is " + XRai.getPassagePaths(z) +"\n");
         if (previous) window.dump("Previous passage is " + XRai.getPassagePaths(previous) + "\n");
      }
      
      // Check for merge
      if (z && XRai.nextElementTo(z.lastElement,x) == x) {
         x = z.parentNode;
      } else if (z && XRai.isBetween(x,z.parentNode,z.lastElement)) {
         if (XRai.isBetween(y,z.parentNode,z.lastElement)) {
            if (debug) window.dump("Highlight is within a single segment\n");
            return;
         }
         x = z.parentNode;
      }

      var toadd = new Array(previous,x,y);

      // (2) Unhighlight until we are in y
      while (z && XRai.isAfter(y,z.lastElement)) {
         XRai.clearPassage(z);
         toremove.push(z);
         z = z.nextPassage;
         window.dump("Next conflicting passage is " + XRai.getPassagePaths(z) +"\n");
      }

      // (3) Merge y with the current z
      if (z && (XRai.isBetween(y,z.parentNode,z.lastElement) || (XRai.nextElementTo(y,z.parentNode) == z.parentNode))) {
         p.lastElement = z.lastElement;
         toadd[2] = z.lastElement;
         XRai.clearPassage(z);
         toremove.push(z);
      } 

      // End of highlighting = add the passage
      try {
         for(var i = 0; i < toremove.length; i++) XRai.removePassage(toremove[i]);
         XRai.highlightPassage(XRai.addPassage(toadd[0],toadd[1],toadd[2]));
      } catch (error) {
         alert("Unrecoverable error: you should stop assessing WITHOUT SAVING and fill a bug report\n\n" + error);
      }

   } catch (error) {
      // Restore the highlighting
      if (p) p.parentNode.removeChild(p);
      for(var i = 0; i < highlighted.length; i++) highlighted[i].removeAttribute("marked");
      for(var i = 0; i < unhighlighted.length; i++) unhighlighted[i].setAttribute("marked",1);
      alert("Recoverable error: you should save your work and fill a bug report\n\n" + error);
      return;
   }


   if (debug) {
      window.dump("Highlighted passages are:\n");
      for(var x = XRai.firstPassage; x != null; x = x.nextPassage) {
         window.dump("* " + XRai.getPassagePaths(x) + "\n");
         if (x.nextPassage && x.nextPassage.previousPassage != x) window.dump("!!!\n");
      }
   }
}



// ========== @p5
// ========== Assessing
// ==========

/**
    Show the eval panel
*/
XRai.showEvalPanel = function(passage,px,py) {
  var eval = document.getElementById("eval_div");

   // Check for valid assessments
   // ie a >= max(children) && <= any ancestor
   var max=max_exhaustivity;
   var min = 1;
   if (debug) window.dump("Assessement must be in [" + min + "," + max + "]\n");

   // Disable invalid assessements
   for(var i = 1; i <= max_exhaustivity; i++) {
      var x = document.getElementById("assess_" + i);
      x.className = (i >= min && i <= max ? null : "disabled");
   }

  // Too small access
  document.getElementById("assess_TS").className = passage.lastElement ? "disabled" : null;
  // No below
  var nb = document.getElementById("nobelow");
  nobelow = passage.hasAttribute("nobelow") ? true : false;
  nb.className = min > 0 ? "disabled" : (nobelow ? "on" : null);
  // Go down
  document.getElementById("eval_breakup_link").className = passage.hasAttribute("nobelow") ? null : "disabled";
  show_div_xy(px,py,   "eval_div");
  return true;

}

// The user has clicked
XRai.assess = function(img, a, event) {
   if (img.className == "disabled") { event.stopPropagation(); return; }

   if (a == "TS") {
   } 
   return true;
}

