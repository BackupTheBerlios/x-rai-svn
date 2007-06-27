#!/usr/bin/php
<?php
/*
    addTopic.php
    Add a topic
    
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
