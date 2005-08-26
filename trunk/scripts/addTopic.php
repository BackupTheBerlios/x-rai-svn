#!/usr/bin/php
<?php

chdir("..");

require_once("include/xrai.inc");
require_once("include/assessments.inc");

if (sizeof($_SERVER["argv"]) != 2)
   die("addTopic <topic file>\n");

$file=$_SERVER["argv"][1];
if (!is_file($file))
   die("'$file' is not a file\n");

print "Starting processing of topic file '$file'\n";


// Parse
$file_content = file_get_contents($file);
$doc = domxml_open_mem("$file_content");
if (!$doc) exit("Error");

$ctx = $doc->xpath_new_context();

$r_topic_id = $ctx->xpath_eval("/inex_topic/@topic_id");
$topic_id = $r_topic_id->nodeset[0]->value;

$r_topic_type = $ctx->xpath_eval("/inex_topic/@query_type");
$topic_type = $r_topic_type->nodeset[0]->value;

print "Topic id is $topic_id and query type is $topic_type\n";
$res = $xrai_db->autoExecute($db_topics, array("id" => $topic_id, "type" => $topic_type, "definition" => $file_content));


?>