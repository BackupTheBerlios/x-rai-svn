
#    download_pools.sh
#    Download the different pools and create a downloadable archive
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

if [ $# -lt 3 ]; then
   echo "download_pools.sh <name> <archive directory> <state> [options]"
   exit 1
fi
   
if ! readlink -f "$0" >& /dev/null; then
   echo "readlink does not exist"
   exit 1
fi

NAME="$1"
OUTDIR="$(readlink -f "$2")"
STATE="$3"
shift 3

OUTTGZ="$OUTDIR/$NAME-$(date +%Y%m%d-%H%M%S).tgz"
TMPDIR=/tmp/$NAME
SDIR="$(dirname "$(readlink -f "$0")")"

echo "State: $STATE"
echo "Script directory: $SDIR"
echo "Temp directory: $TMPDIR"
echo "Output archive: $OUTTGZ"
if test -d "$TMPDIR"; then 
   echo "Warning!!! Erasing temp dir $TMPDIR"
fi
   
echo "Type \"yes\" to confirm"
read confirm
if test "$confirm" != "yes"; then
   echo "Not confirmed ($confirm)"
   exit
fi
   
echo
echo "Let's go!"

rm -rf $TMPDIR
mkdir $TMPDIR
echo "Command line: php -d memory_limit=128M $SDIR/download_pools.php $@ official $TMPDIR"
if php -d memory_limit=128M $SDIR/download_pools.php "$@" "$STATE" $TMPDIR > $TMPDIR.log 2>&1; then
   (cd /tmp; tar c "$NAME") | gzip -c > "$OUTTGZ"
else
   echo "Error while downloading the pools" 1>&2
fi

rm -rf $TMPDIR
