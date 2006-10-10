#!/bin/sh

# addPools.sh
# Add several pools calling addPool.php

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

