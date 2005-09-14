/**
   INEX javascript code
   B. Piwowarski, 2003
*/
var XRai = new Object(); // used for namespace

XRai.click = function(event) {
   if (event.target.localName == "a") {
      if (XRai.beforeunload) {
         return XRai.beforeunload(null);
      }
   }
   return true;
}

var is_gecko = true;
var xhtml_ns = "http://www.w3.org/1999/xhtml";
var document_is_loaded = false;

// -*- Utilities -*-

function document_loaded() {
   document_is_loaded = 1;
}

function findPosX(obj)
{
   var curleft = 0;
   if (obj.offsetParent)
   {
      while (obj.offsetParent)
      {
         curleft += obj.offsetLeft
         obj = obj.offsetParent;
      }
   }
   else if (obj.x)
      curleft += obj.x;
   return curleft;
}

function findPosY(obj)
{
   var curtop = 0;
   if (obj.offsetParent)
   {
      while (obj.offsetParent)
      {
         curtop += obj.offsetTop
         obj = obj.offsetParent;
      }
   }
   else if (obj.y)
      curtop += obj.y;
   return curtop;
}
function contains(a, b) {
  // Return true if node a contains node b.
  while (b != a)
    if (b == null) return false;
    else b = b.parentNode;
  return true;
}

function _isMouseInside(obj,event) {
  // determine if mouse is over object
  var mouseX = event.clientX;
  var mouseY = event.clientY;
  var objTop = obj.offsetTop;
  var objBottom = obj.offsetTop + obj.offsetHeight;
  var objLeft = obj.offsetLeft;
  var objRight = obj.offsetLeft + obj.offsetWidth;
//   alert(mouseX + "," + mouseY + " : x=(" + objLeft + ", " + objRight + ") y=("+ objTop + ", " + objBottom + ")");
  return (mouseX > objLeft && mouseX < objRight &&  mouseY > objTop && mouseY < objBottom);
}

// -*- Menu -*-


var current_menu;

function hide_menu(menu) {
  if (!menu) return;
  menu.style.visibility = "hidden";
  if (current_menu == menu) current_menu = 0;
  document.getElementById("a_" + menu.id).style.background = "";
}

function hide_menu_id(id) {
//   alert(id + " " + document.getElementById(id));
  hide_menu(document.getElementById(id));
  return true;
}

function menuout(event) {
  var current, related;
/*  if (window.event) {
    current = this;
    related = window.event.toElement;
  }
  else {*/
    current = event.currentTarget;
    related = event.relatedTarget;
//   }
  if (related && contains(current,related)) return;
  var s='';
  if (_isMouseInside(current,event)) return;
   hide_menu(current);
//   s+='phase: ' + event.eventPhase;
//   s+=', related: '; if (related) s += related + " (" + related.id + ")";
//   s += ', target:'; if (event.target) s += event.target + " (" + event.target.id + ")";
//    alert(s);

}

function show_menu(x,y) {
    if (current_menu) {
      var id = current_menu.id;
      hide_menu(current_menu);
//       if (id == y) return;
    }
    var menu = document.getElementById(y);
    menu.style.left = findPosX(x)  + "px";
    menu.style.top = findPosY(x) + x.scrollHeight + "px";
    menu.style.visibility = "visible";
    current_menu = menu;
    x.style.background = "yellow";
 }


// Returns the absolute minimum position
function get_inner_top() {
   var menu = document.getElementById("menubar");
   if (window.opera) return window.pageYOffset + menu.offsetTop + menu.offsetHeight;
   if (window.scrollY) return window.scrollY + menu.offsetTop + menu.offsetHeight;
}

function get_inner_bottom() {
   var s_div = document.getElementById("s_div");

   if (window.opera) return window.pageYOffset + (s_div ? window.innerHeight : s_div.offsetTop);
   return window.scrollY + (s_div ? window.innerHeight : s_div.offsetTop);
//    if (s_div) bottom -= window.innerHeight + window.scrollY
}

function show_div_xy(x,y,id) {
    var e = document.getElementById(id);
    var s_div = document.getElementById("stat_div");
    var b = get_inner_bottom();
    if (y + e.scrollHeight > b) y = b - e.scrollHeight;
    var t = get_inner_top();
    if (y < t) y = t;

    if ((x + e.scrollWidth) > (window.innerWidth + window.scrollX))
          x = window.innerWidth + window.scrollX - e.scrollWidth - 15;
    if  (x<0) x = 5;

    e.style.left = x + "px";
    e.style.top = y + "px";
   e.style.visibility = "visible";
}

function show_div(event,id) {
   e = document.getElementById(id);
   if (is_gecko) {
   var x = (event.pageX + 5);
    var y = event.pageY + 5;
    var s_div = document.getElementById("stat_div");
    if ((y + e.scrollHeight + (s_div ? s_div.scrollHeight: 0)) > (window.innerHeight + window.scrollY)) y = y - e.scrollHeight - 15;
    if ((x + e.scrollWidth) > (window.innerWidth + window.scrollX)) x = x - e.scrollWidth - 15;

    if (y < 0) y = 5;
    if  (x<0) x = 5;
    e.style.left = x + "px";
    e.style.top = (y+5) + "px";
//    alert("Pos=" + event.pageX + "," + event.pageY + ", E style of " + e + "," + e.style.top);
   } else {
      alert(event.y + "," + document.body.offsetTop + "," + document.body.clientHeight);
      e.style.left = (event.x + document.body.scrollLeft + 5) + "px";
      e.style.top = (event.y + document.body.scrollTop + document.body.clientHeight + 5) + "px";
   }
   e.style.visibility = "visible";
}

function hide_div(id) {
     document.onmousemove="";
     document.getElementById(id).style.visibility="hidden";
     return false;
}

function get_position (e) {
   if (document.getBoxObjectFor) {
      var x = document.getBoxObjectFor(e);
      return { x: x.x, y: x.y, height: x.height }
   }
  if (document.layers) {
     return { x: e.x, y: e.y, height: e.offsetHeight };
  }
  else if (document.getElementById) {
    var coords = {x: 0, y: 0, height: e.offsetHeight };
    while (e) {
      coords.x += e.offsetLeft;
      coords.y += e.offsetTop;
      e = e.offsetParent;
    }
    return coords;
  }
}




function scroll_to_element(e,top) {
  var coords = get_position(e);

  var y_min = get_inner_top(), y_max = get_inner_bottom();
  var y_top = coords.y;
  var y_bottom = coords.y + coords.height;

//   Message.show("notice","Pos: " + coords.y + " / "+ coords.height + " / " + y_min + "," + y_max, 4800);

  //   alert("object=" + coords.y  + ", " + y_top + ", " + y_bottom + " and view=" + y_min + "," + y_max);
  if (y_top < y_min || y_bottom > y_max) {
     scrollTo(0,y_top - (y_min - window.pageYOffset));
  }
//   else if (y_bottom > y_max) {
//        var y = y_bottom + window.scrollY - y_max ;
//      scrollTo(0,y);
//   }

  return true;
}


var todo_index = -1;
var toRestore = new Array();

function restore_focus(id) {
   var e = toRestore.pop();
   if (e)  {
      e.removeAttribute("focus");
   }
}

function show_focus(e) {
   if (!e) return;
   scroll_to_element(e,20);

   if (e.focus) { e.focus(); e.focus(); }
   else {
      // article view
      e.setAttribute("focus","yes");
      toRestore.push(e);
      setTimeout('restore_focus()',1000);
   }
}



function hideEval(){
//      document.onmousemove="";
    hide_div("eval_div");
     return false;
}

function restore_xml(id) {
  document.getElementById(id).firstChild.style.border="0";
}

// Find siblings
var sibling_list;
var sibling_path;

function get_parent_id(path) {
    var i = path.lastIndexOf('/');
    if (i == -1) return false;
    return path.substring(0,i);
}

function find_child(e, path) {
   if (!e.firstChild) return;
   if (e.firstChild.nodeType == 1 && e.firstChild.getAttribute("class") == "xml") {
      var id =  e.getAttribute("id");
      if (id == sibling_path) return;
      var i = id.lastIndexOf('/');
      if (i != -1) id = id.substr(i+1);
      if (!sibling_list) sibling_list = ""; else sibling_list += ", ";
      sibling_list += id;
      return;
   }
   var children = e.childNodes;
   for (var i = 0; i < children.length; i++) find_child(children[i]);
}

function find_siblings(path) {
   // Find parent
   sibling_path = "a_" + path;
    var s = get_parent_id(sibling_path);
   if (!s) return false;
    var e = document.getElementById(s);
    if (!e) return "???";

   // Find children
   sibling_list = false;
   var children = e.childNodes;
   for (var i = 1; i < children.length; i++) find_child(children[i]);
   return sibling_list;

}


// Show right panel

var current_right_panel = { element: null, image: null};
function toggle_right_panel(element, image) {
  if (current_right_panel.element) current_right_panel.element.style.visibility = "hidden";
  if (current_right_panel.image) current_right_panel.image.className = "";
  if (current_right_panel.element == element) {
    current_right_panel.element = current_right_panel.image = null;
    return; // exit (close the view)
  }
  current_right_panel.element = element; element.style.visibility = "visible";
  current_right_panel.image= image;
  if (image) image.className = "selected";
}

function toggle_panel(id, imageId) {
  var x = document.getElementById(id);
  var b = x.style.visibility == "visible";
  x.style.visibility = b ? "hidden" : "visible";
  var image = document.getElementById(imageId);
  if (image) image.className = b ? null : "selected";
}

XRai.gotoLocation = function(url) {
  if (XRai.beforeunload) if (!XRai.beforeunload(null)) return false;
   window.location = url;
}

function collection_keypress(event) {
   event.stopPropagation();
   var C = event.ctrlKey;

//    Message.show("notice",event.which + "," + (!C && event.which == 51));
   if ((C && (event.which == 37)) || (!C && event.which == 49)) todo_previous();
   else if (((C && event.which == 38) || (!C && event.which == 50)) && up_url) XRai.goUp();
   else if ((C && event.which == 39)  || (!C && event.which == 51)) { todo_next(); }
   else if (C && (event.which == 73)) right_panel('informations','img_informations',base_url + '/iframe/informations.php');


//     XRai.debug("Key pressed: charchode=" + event.charCode
//     + ", keycode=" + event.which
//     + ", which=" + event.which + ", shiftKey=" + event.shiftKey + ", ctrlKey=" + event.ctrlKey
    //     + ", x= " + event.pageX + "\n");

  // Desactive ALL shortcuts for security reasons
//   event.stopPropagation();
//   return false;
   return true;
}

function right_panel(id,img_id,src) {
  var x = document.getElementById(id);
    if (x.src == "" || src == null) {
      var menubar = document.getElementById("menubar");
      x.style.top = menubar.scrollHeight + "px";
      if (src) x.src= src;
    }
    toggle_right_panel(x,document.getElementById(img_id));
}

/* From http://www.webreference.com/js/column8/functions.html
   name - name of the cookie
   value - value of the cookie
   [expires] - expiration date of the cookie
     (defaults to end of current session)
   [path] - path for which the cookie is valid
     (defaults to path of calling document)
   [domain] - domain for which the cookie is valid
     (defaults to domain of calling document)
   [secure] - Boolean value indicating if the cookie transmission requires
     a secure transmission
   * an argument defaults when it is assigned null as a placeholder
   * a null placeholder is not required for trailing omitted arguments
*/

function setCookie(name, value, expires, path, domain, secure) {
   var curCookie = name + "=" + escape(value) +
         ((expires) ? "; expires=" + expires.toGMTString() : "") +
         ((path) ? "; path=" + path : "") +
         ((domain) ? "; domain=" + domain : "") +
         ((secure) ? "; secure" : "");
   document.cookie = curCookie;
}
/*
  name - name of the desired cookie
  return string containing value of specified cookie or null
  if cookie does not exist
*/

function getCookie(name) {
   var dc = document.cookie;
   var prefix = name + "=";
   var begin = dc.indexOf("; " + prefix);
   if (begin == -1) {
      begin = dc.indexOf(prefix);
      if (begin != 0) return null;
   } else
      begin += 2;
      var end = document.cookie.indexOf(";", begin);
      if (end == -1)
            end = dc.length;
      return unescape(dc.substring(begin + prefix.length, end));
}


/*
   name - name of the cookie
   [path] - path of the cookie (must be same as path used to create cookie)
   [domain] - domain of the cookie (must be same as domain used to
     create cookie)
   path and domain default if assigned null or omitted if no explicit
     argument proceeds
*/

function deleteCookie(name, path, domain) {
   if (getCookie(name)) {
      document.cookie = name + "=" +
               ((path) ? "; path=" + path : "") +
               ((domain) ? "; domain=" + domain : "") +
               "; expires=Thu, 01-Jan-70 00:00:01 GMT";
   }
}

// date - any instance of the Date object
// * hand all instances of the Date object to this function for "repairs"

function fixDate(date) {
   var base = new Date(0);
   var skew = base.getTime();
   if (skew > 0)
         date.setTime(date.getTime() - skew);
}



// =========
// ========= Help management
// =========

var help_stylesheet = null;
if (document.styleSheets) for(var i = 0; i < document.styleSheets.length; i++) {
   if (document.styleSheets[i].title == "help") {
   help_stylesheet = document.styleSheets[i];
   break;
   }
}
if (getCookie("no_help") == 1 && help_stylesheet) help_stylesheet.disabled = true;

function toggle_help() {
   // create an instance of the Date object
   var now = new Date();
   fixDate(now);
   now.setTime(now.getTime() + 15 * 24 * 60 * 60 * 1000)

   var value = getCookie("no_help");
   if (value == null) value = 1;
   value = 1 - value;
   // 15 day cookie
   setCookie("no_help",value,now);
   var x = document.getElementById("a_help");
   if (!x) alert("Bug: no <a> tag for help!?!");
   else x.setAttribute("class",value ? "" : "on");
   if (help_stylesheet) help_stylesheet.disabled = value ? true : false;
}



// =========
// ========= Message
// =========

var Message = {
   // Unique message id generator
   message_id: 0,

   show: function (type,msg, duration) {
      if (!duration) duration = 1200;
      Message.showDuring(type,msg,duration);
   },

   showDuring: function (type,msg, time) {
      var div = document.createElement("div");
      div.appendChild(document.createTextNode(msg));
      div.setAttribute("class","message_" + type);
      Message.message_id++;
      div.id = "message_" + Message.message_id;
      document.getElementById("body").appendChild(div);
      setTimeout('Message.clear("' + div.id + '")',time);
   },

   clear: function (id) {
      var x = document.getElementById(id);
      if (x) x.parentNode.removeChild(x);
   }
}



XRai.goUp = function() {
if (up_url) window.location = up_url;
}
