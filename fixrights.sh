#!/bin/sh
chown -R dam:cms /var/www/dev.videosquare.eu
chmod -R g+w /var/www/dev.videosquare.eu
chown -R xtro:cms /var/www/dev.videosquare.eu/httpdocs/flash/
chown -R www-data:www-data /var/www/dev.videosquare.eu/data/cache

chown -R dam:cms /var/www/videosquare.eu
chmod -R g+w /var/www/videosquare.eu
chown -R xtro:cms /var/www/videosquare.eu/httpdocs/flash/
chown -R www-data:www-data /var/www/videosquare.eu/data/cache
