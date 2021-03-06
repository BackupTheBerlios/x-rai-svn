#!/bin/sh

#    organiseFiles.sh
#    Sort files with a numeric name into a hierarchy of directories
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


function usage {
   echo "organizeFiles.sh <SUFFIX> <DEST> <SRC1> <SRC2> ..." 1>&2
   exit
}

test $# -ge 3 || usage
exit

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
            mv "$f"  "$dest/$d/$file$suffix"
         done
done