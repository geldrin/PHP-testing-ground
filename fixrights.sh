#!/bin/sh
chown -R dam:cms /var/www/dev.video.teleconnect.hu
chmod -R g+w /var/www/dev.video.teleconnect.hu
chown -R xtro:cms /var/www/dev.video.teleconnect.hu/httpdocs/flash/

chown -R dam:cms /var/www/video.teleconnect.hu
chmod -R g+w /var/www/video.teleconnect.hu
chown -R xtro:cms /var/www/video.teleconnect.hu/httpdocs/flash/
