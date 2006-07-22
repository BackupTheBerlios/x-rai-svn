#!/bin/bash

dir="$(dirname $(readlink -f "$0"))"
find . -name "*.xml" | while read file; do echo -n "$(echo $file | sed -e 's/\.\/part-.*\///' -e 's/\.xml$//') "; perl $dir/gettitle.pl $file; echo; done
