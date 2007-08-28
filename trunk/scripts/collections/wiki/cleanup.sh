#!/bin/sh

#    cleanup.sh
#    Cleanup for the wikipedia collection, put the xrai:s tags, and
#    organize the files within subdirectories
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

# Final cleanup for wikipedia: 
# - call the annote process & mixed content handling
# - create directories (1.000 files max per directory)
# (c) 2006 B. Piwowarski


function error {
   echo "$@" 1>&2
   exit 1
}

destdir="$1"
tmpdir="/tmp/wiki2xrai.$$"
test $# -lt 2 && error "Synopsis: cleanup.sh <OUTDIR> <SRCDIR1> <SRCDIR2> <...>"
shift

# Basedir is two steps away
BASEDIR="$(dirname "$(readlink -f "$0")")"/../../../lingpipe

   


test -d "$destdir" ||  error "Destination directory $destdir does not exist"S

ant -quiet -buildfile $BASEDIR/build.xml || echo "Cannot build/check that annotate.jar is well up to date"

if ! test -f $BASEDIR/annotate.jar; then
	echo "Cannot find the annote.jar file" 1>&2
	exit 1
fi

echo "STARTING (LINGPIPE=$BASEDIR, $origdir TO $destdir)"

for i in "$@"; do
echo "================== IN $i ($tmpdir) ====================="
   java -Dorg.xml.sax.driver=org.apache.xerces.parsers.SAXParser -cp $BASEDIR/annotate.jar:$BASEDIR/lib/lingpipe-2.0.0.jar:$BASEDIR/lib/xercesImpl.jar:$BASEDIR/lib/xml-apis.jar AnnotateCmd -model=$BASEDIR/EN_NEWS.model -fileSuffixes="xml" -contentType="text/xml; charset=UTF-8" -elements="p,caption,item" -inputDir="$i" -outputDir="$tmpdir"  2>&1 | grep -v "^Processing file"
   
   find $tmpdir -type f -name "*.xml" |
      while read f; do
         file=$(basename $f .xml)
         r=$(($file / 1000))
         d=$(($r / 100 % 100))/$(($r % 100))
         echo "Moving $file in $destdir/$d/$file"
         mkdir -p "$destdir/$d"
         mv "$f"  "$destdir/$d/$file.xml"
      done
      
   rm -fr $tmpdir
done
