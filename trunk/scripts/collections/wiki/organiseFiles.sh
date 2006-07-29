#!/bin/sh

# Sort files with a numeric name into a hierarchy of directories

test $# -ge 3 || exit

suffix="$1"
dest="$2"
shift 2
test -d "$dest" || exit

while test $# -ge 1; do
   src="$1"
   shift
   if test ! -d "$src"; then echo "$src does not exist."; continue; fi
   
   find "$src" -type f -name "*$suffix" |
         while read f; do
            dir="$(dirname "$(readlink -f "$0")")"
            file=$(basename $f $suffix)
            r=$(($file / 1000))
            d=$(($r / 100 % 100))/$(($r % 100))
            mkdir -p "$dest/$d"
            cp "$f"  "$dest/$d/$file$suffix"
         done
done