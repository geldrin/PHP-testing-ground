#!/bin/sh
chown -R dam:cms /var/www/dev.video.teleconnect.hu
chmod -R g+w /var/www/dev.video.teleconnect.hu
chown -R xtro:cms /var/www/dev.video.teleconnect.hu/httpdocs/flash/

chown -R dam:cms /var/www/videosquare.eu
chmod -R g+w /var/www/videosquare.eu
chown -R xtro:cms /var/www/videosquare.eu/httpdocs/flash/
