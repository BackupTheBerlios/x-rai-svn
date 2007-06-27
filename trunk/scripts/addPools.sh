#!/bin/sh

#    addPools.sh
#    Add pools using an association file (userid, topicid)
#    
#    Copyright (C) 2003-2007  Benjamin Piwowarski benjamin@bpiwowar.net
#    
#    This library is free software; you can redistribute it and/or
#    modify it under the terms of the GNU Library General Public
#    License as published by the Free Software Foundation; either
#    version 2 of the License, or (at your option) any later version.
#    
#    This library is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
#    Library General Public License for more details.
#    
#    You should have received a copy of the GNU Library General Public
#    License along with this library; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA


function synopsis {
   echo "$1" 1>&2
   echo "Synopsis: addPools.sh <state> <default collection> <pool dir> [<association file>]"
   exit $2
}
   
function log {
   echo "$@" 1>&2
}

basedir="$(dirname "$(readlink -f "$0")")"
test $# -eq 3 || test $# -eq 4 || synopsis "Wrong number of arguments" 1
  
state="$1"
default="$2"
dir="$3"
file="$4"



# die("addPool [-update <poolid>] <state> <userid> <name> <default collection> <pool file>\n");

(if test -z "$file"; then cat; else cat "$file"; fi) | while read topic login; do
   log "Adding pool for topic $topic and login $login"
   poolfile="$dir/$topic.xml"
   if test -f "$poolfile"; then
      php "$basedir"/addPool.php "$state" "$login" "Topic for pool $topic" "$default" "$poolfile"
   else
      log "Error: pool file $poolfile not found"
   fi   
done

