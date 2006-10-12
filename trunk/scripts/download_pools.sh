if [ $# -ne 2 ]; then
   echo "download_pools.sh <name> <archive directory>"
   exit 1
fi
   
if ! readlink -f "$0" >& /dev/null; then
   echo "readlink does not exist"
   exit 1
fi

NAME="$1"
OUTDIR="$(readlink -f "$2")"
OUTTGZ="$OUTDIR/$NAME-$(date +%Y%m%d-%H%M%S).tgz"
TMPDIR=/tmp/$NAME
SDIR="$(dirname "$(readlink -f "$0")")"

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
if php -d memory_limit=128M $SDIR/download_pools.php  official $TMPDIR > $TMPDIR.log 2>&1; then
   (cd /tmp; tar c "$NAME") | gzip -c > "$OUTTGZ"
else
   echo "Error while downloading the pools" 1>&2
fi

rm -rf $TMPDIR
