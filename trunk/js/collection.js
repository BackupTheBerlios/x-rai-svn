//

function goUp() {
   if (up_url) window.location = up_url;
}

function todo_previous() {
	if (!todo || todo.length == 0) {
		if (confirm("No more elements to assess in this view. Do you like to jump to the previous view where there is an element to assess ?")) {
			window.location = base_url + "/article.php?view_jump=1&id_pool=" + id_pool + "&next=0&view_xid=" + view_xid;
		}
		return;
	}
	if (todo_index > 0) todo_index = (todo_index - 1) % todo.length;
	else todo_index = todo.length - 1;
	var e = document.getElementById(todo[todo_index]);
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
	var e = document.getElementById(todo[todo_index]);
	show_focus(e);
}
