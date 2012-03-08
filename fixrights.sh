#!/bin/sh
chown -R dam:www-data /var/www/dev.video.teleconnect.hu
chmod -R g+w /var/www/dev.video.teleconnect.hu
chown -R xtro:www-data /var/www/dev.video.teleconnect.hu/httpdocs/flash/

chown -R dam:www-data /var/www/video.teleconnect.hu
chmod -R g+w /var/www/video.teleconnect.hu
chown -R xtro:www-data /var/www/video.teleconnect.hu/httpdocs/flash/
