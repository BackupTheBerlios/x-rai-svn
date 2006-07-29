#!/bin/sh

# GPL licence, see copyright file
#(c) B. Piwowarski, 2006

# Read a file with two columns: id and wiki name

# CREATE TABLE wikienimages (id integer not null primary key, name varchar(255) not null, constraint unique_name unique (name), width integer not null, height integer not null);

database="inex2006.wikienimages"

file="$1"
if ! test -f "$file"; then echo "Cannot find the file '$file'"; exit -1; fi

dir="$2"
if ! test -d "$dir"; then echo "Cannot find the directory '$dir'"; exit -1; fi

echo "Processing..."
cat "$file" | while read id name; do
   name="$(echo "$name" | sed -e "s/'/\\\\'/g")"
   echo "INSERT INTO $database VALUES($id, '$name', 0, 0);";
done


