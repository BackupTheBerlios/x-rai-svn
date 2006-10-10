#!/usr/bin/php
<?php

$_SERVER["REMOTE_USER"] = "root";

$olddir = getcwd();
chdir(dirname(__FILE__) . "/..");
require_once("include/xrai.inc");
require_once("include/assessments.inc");
chdir($olddir);

if (sizeof($_SERVER["argv"]) != 2)
   die("addTopic <topic file>\n");

$file=$_SERVER["argv"][1];
if (!is_file($file))
   die("'$file' is not a file\n");

print "Starting processing of topic file '$file'\n";


// Parse
$file_content = file_get_contents($file);
$doc = DOMDocument::loadXML("$file_content");
if (!$doc) exit("Error");

$xpath = new DOMXPath($doc);

if (!isset($topicid_xpath)) 
	$topicid_xpath = "/inex_topic/@topic_id";
	
$r_topic_id = $xpath->query($topicid_xpath);
$topic_id = $r_topic_id->item(0)->nodeValue;

print "Topic id is $topic_id\n";
$res = $xrai_db->autoExecute($db_topics, array("id" => $topic_id, "type" => $topic_type, "definition" => $file_content));


?>
