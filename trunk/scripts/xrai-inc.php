<?php
/*
    xrai-inc.php
    Includes the necessary files for configuration

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

// we are root!
$_SERVER["REMOTE_USER"] = "root";

// Include and get back to the current directory
$olddir = getcwd();
chdir(dirname(__FILE__) . "/../web");
require_once("include/xrai.inc");
require_once("include/assessments.inc");
chdir($olddir);
?>
