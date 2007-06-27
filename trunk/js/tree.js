/*

    tree.js
    JS code for the XML tree view
            
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
