#!/bin/sh

#    images.sh
#    Read a file with two columns: id and wiki name
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


