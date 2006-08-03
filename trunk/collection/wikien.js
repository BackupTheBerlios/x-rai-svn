/** Specific code for wikipedia */

XRai.wiki_click_ok = false;

function collectionBeforeClick(event) {
   XRai.wiki_click_ok = ! XRai.BEPMode;
   if (debug) XRai.debug("CLICK OK: " + XRai.wiki_click_ok);
}

function collectionOnClick(event) {
  if (debug) XRai.debug("Mouse click in phase " + event.eventPhase + "/" + event.BUBBLING_PHASE + " / " + event.CAPTURING_PHASE + " / " + event.target.localName);
  if (event.eventPhase != 3) return;
  if (XRai.wiki_click_ok && (event.target.localName == "collectionlink") && XRai.beforeunload()) {
     var l = event.target.getAttributeNS("http://www.w3.org/1999/xlink","href")
     if (l) window.location = l;
     else Message.show("warning","Could not find the URL for this document");
  }
}

XRai.initFunctions.push(function() { 
   if (debug) XRai.debug("Set WIKI click handler");
   XRai.getRoot().addEventListener("mousedown", collectionBeforeClick, true);
   XRai.getRoot().addEventListener("click", collectionOnClick, true);
});

if (debug) XRai.debug("wikien.js loaded");
