function collectionOnClick(event) {
  // alert(event);
  if (event.target.localName == "collectionlink") {
//     window.location = "coucou";
  }
  event.stopPropagation();
}