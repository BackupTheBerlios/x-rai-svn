/** Specific code for wikipedia */

function collectionOnClick(event) {
  if (event.target.localName == "collectionlink") {
     var l = event.target.getAttributeNS("http://www.w3.org/1999/xlink","href")
     if (l) window.location = l;
     else Message.show("warning","Could not find the URL for this document");
  }
  event.stopPropagation();
}

document.addEventListener("load", function() { 
   XRai.getRoot().addEventListener("mouseup", collectionOnClick);
},false);