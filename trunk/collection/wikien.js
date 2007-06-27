/*

    wikien.js
    specific js code for the wikipedia collection (handling clicks on internal
    wikipedia links)
    
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
