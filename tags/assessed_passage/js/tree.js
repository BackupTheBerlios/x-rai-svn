
// Find the next node (the next sibling, ...)

function toggle_node(node) {
  var x = node.parentNode.nextSibling;
  if (!x) return;
  if (x.className != "visible") {
    node.src = baseurl + 'img/tree_minus.png';
    x.className = "visible";   
  } else {
    node.src = baseurl + 'img/tree_plus.png';
    x.className = "hidden";
  }
}

function goto_path(node) {
  var x = node;
  var s = "";
  var t = "";
  while ((x = x.parentNode.parentNode) && x.className == "in") s =  "/" + x.getAttribute("name") + s;
   x = parent.get_xrai_element_by_path(s);
   if (x) x = parent.get_first_xml(x);
   if (x) parent.show_focus(x);
}

document.onkeypress = parent.document.onkeypress;
