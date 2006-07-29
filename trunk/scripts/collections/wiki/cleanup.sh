#!/bin/sh

# Final cleanup for wikipedia: 
# - call the annote process & mixed content handling
# - create directories (1.000 files max per directory)
# (c) 2006 B. Piwowarski

# to be called from the annoted directory only

origdir="$1"
destdir="$2"
tmpdir="/tmp/wiki2xrai.$$"
BASEDIR=$HOME/logiciels/lingpipe

test -d "$origdir"/part-0 || exit
test -d "$destdir" || exit


for i in $origdir/part-*; do
   echo "================== IN $i ($tmpdir) ====================="
   java -Dorg.xml.sax.driver=org.apache.xerces.parsers.SAXParser -cp $BASEDIR/demos/command/command-demo.jar:$BASEDIR/lingpipe-2.0.0.jar:$BASEDIR/lib/xercesImpl.jar:$BASEDIR/lib/xml-apis.jar AnnotateCmd -model=$BASEDIR/demos/models/EN_NEWS.model -fileSuffixes="xml" -contentType="text/xml; charset=UTF-8" -elements="p,caption,item" -inputDir="$i" -outputDir="$tmpdir" 2>&1 | grep -v "^Processing file"
   
   find $tmpdir -type f -name "*.xml" |
      while read f; do
         dir="$(dirname "$(readlink -f "$0")")"
         file=$(basename $f .xml)
         r=$(($file / 1000))
         d=$(($r / 100 % 100))/$(($r % 100))
      #      echo "$file: $d"
      
         mkdir -p "$destdir/$d"
         cp "$f"  "$destdir/$d/$file.xml"
      done
      
   rm -fr $tmpdir
done