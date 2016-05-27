#!/bin/bash
# -*- sh -*-
# This plugin needs to be run as root. Other config required in /etc/munin/plugin-conf.d/munin-node:
# [vsq_live.sh]
# user root

: << =cut

=head1 NAME

nginx stream - Plugin to measure the number of nginx live streams

=head1 AUTHOR

Videosquare Ltd.

=head1 LICENSE

Videosquare

=head1 MAGIC MARKERS

 #%# family=auto
 #%# capabilities=autoconf

=cut

. $MUNIN_LIBDIR/plugins/plugin.sh

if [ "$1" = "autoconf" ]; then
        echo yes
        exit 0
fi

if [ "$1" = "config" ]; then

        echo 'graph_title Videosquare reflector streams'
        echo 'graph_args --base 1000 -l 0 '
        echo 'graph_scale no'
        echo 'graph_vlabel Number of streams'
        echo 'graph_category videosquare'
        echo 'vsq_rtmp.label RTMP'
        echo 'vsq_rtmp.draw AREA'
        echo 'vsq_http.label HTTP'
        echo 'vsq_http.draw AREA'
        echo 'vsq_https.label HTTPS'
        echo 'vsq_https.draw AREA'
        print_warning videosquare
        print_critical videosquare
        exit 0
fi

# Get hostname
tmp=`hostname --all-ip-addresses`
myip=`echo ${tmp} | xargs`

# RTMP live
echo -n "vsq_rtmp.value "
netstat -n -p | grep nginx | grep "${myip}:1935" | wc -l

# HTTP live
echo -n "vsq_http.value "
netstat -n -p | grep nginx | grep "${myip}:80" | wc -l

# HTTPS live
echo -n "vsq_https.value "
netstat -n -p | grep nginx | grep "${myip}:443" | wc -l