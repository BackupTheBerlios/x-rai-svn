<?php

// Also have a look at settings-default
// that contain default values that might be changed
// if needed (this file is included latter, so values
// can be modified)

// ========================== DATABASE ACCESS

// Database access, DSN syntax
$xrai_dsn = "pgsql://login:password@host/DBNAME";
// how the true value is represented in the db
$db_true = "t"; 
// Optional schema name (for postgresql)
$db_schema="";


// ========================== ASSESSMENTS

// Highlight only mode (no exhaustivity to be set)
$highlight_only = true;
// Create paths in the database if needed (less secure
// but do not need any indexation)
$create_paths = true;

// ========================== WEBSITE SETTINGS

// Main title of the website
$main_title = "INEX 2007 Assessments";

// Base URL of the server
$base_url="https://....";

// E-mail of the admin
$xrai_admin_email = "root@localhost";

// Display debug messages?
$do_debug=false;

// Associative list of X-Rai admin logins
$root_users = array("root" => true);

// First location steps
$localisation[] = array("X-Rai","$base_url","XML Retrieval Assessment Interface");

// Add menus
add_menu("Links","https://developer.berlios.de/projects/x-rai/","X-Rai: bugs, forums, ...","Project home page");
add_menu("Links","$base_url/misc/ra-guide.pdf","Guide","Relevance Assessment guide");


// ========================== ACCESS TO DOCUMENTS

// Path to documents
$xrai_documents="PATH TO XRAI DOCUMENTS";
$media_url="";

// Functions that returns a file handle for a document
// useful if documents are not locally stored
function getArticle($collection,$filename) {
   
   $fp = fopen("$xml_documents/$collection/$file.xml", "r");
}



?>
