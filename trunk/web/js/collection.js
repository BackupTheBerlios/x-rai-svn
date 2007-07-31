/*
    collection.js
    JS code for the collectionv view (navigation mainly)
        
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


function todo_previous() {
     if (!todo || todo.length == 0) {
          if (confirm("No more elements to assess in this view. Do you like to jump to the previous view where there is an element to assess ?")) {
               window.location = base_url + "/article.php?view_jump=1&id_pool=" + id_pool + "&next=0&view_xid=" + view_xid;
          }
          return;
     }
     if (todo_index > 0) todo_index = (todo_index - 1) % todo.length;
     else todo_index = todo.length - 1;
     var e = todo[todo_index];
     show_focus(e);
}

function todo_next() {
     if (!todo || todo.length == 0) {
          if (confirm("No more elements to assess in this view. Do you like to jump to the next view where there is an element to assess ?")) {
               window.location = base_url +  "/article.php?view_jump=1&id_pool=" + id_pool + "&next=1&view_xid=" + view_xid;
          }
          return;
     }
     todo_index = (todo_index + 1) % todo.length;
     var e = todo[todo_index];
     show_focus(e);
}
