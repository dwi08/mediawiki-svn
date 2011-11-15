#!/bin/bash
#
# This script is used to update a .deb package from the subversion repository
# and then update the WMF apt repository.
#
# Usage: update.sh <package>
#
# Author: Tim Starling 2007,2008,2010

# Exit immediately if a command exits with a non-zero status
set -e

# We do require a package name as argument
if [ -z $1 ];then
	echo "Usage: update.sh <package>"
fi

thisdir=`dirname $0`
package=$1

cd $thisdir
svn up $package

echo "Building package..."
cd $package
prebuild_date=`TZ=UTC0 date +'%Y-%m-%d %H:%M:%SZ'`
sleep 1
dpkg-buildpackage -rfakeroot -aamd64

echo
echo "Uploading files..."
cd ..
tar -v -N"$prebuild_date" -c $package''_* | ssh -A root@apt.wikimedia.org "
	test -e /srv/wikimedia/pool/main/$package || mkdir /srv/wikimedia/pool/main/$package
	tar -C /srv/wikimedia/pool/main/$package -x && \
	echo && \
	echo Updating the repository && \
	update-repository 2>/dev/null && \
	echo Success || \
	echo update-repository failed
	"

