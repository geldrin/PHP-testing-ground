#!/bin/bash
### BEGIN INIT INFO
# Provides:          openoffice-headless
# Required-Start:    $local_fs $remote_fs $syslog
# Required-Stop:     $local_fs $remote_fs $syslog
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Start OpenOffice in headless mode for Videotorium slide conversion
# Description:       Start OpenOffice in headless mode for Videotorium slide conversion of ODP/SXI files.
### END INIT INFO

OOo_HOME=/usr/lib/libreoffice
SOFFICE_PATH=$OOo_HOME/program/soffice.bin
PIDFILE=/var/run/openoffice-server.pid

case "$1" in
start)
    if [ -f $PIDFILE ]; then
	echo "OpenOffice headless server has already started."
	exit
    fi
    echo "Starting OpenOffice headless server"
    su - conv -c "$SOFFICE_PATH --headless --accept=\"socket,host=127.0.0.1,port=8100;urp;\" --nofirststartwizard & > /dev/null 2>&1"
    touch $PIDFILE
    ;;
stop)
    if [ -f $PIDFILE ]; then
	echo "Stopping OpenOffice headless server."
	killall -9 soffice.bin
	rm -f $PIDFILE
	exit
    fi
    echo "Openoffice headless server is not running."
    exit
    ;;
*)
    echo "Usage: $0 {start|stop}"
    exit 1
esac
exit 0
