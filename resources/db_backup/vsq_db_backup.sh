#!/bin/bash

mysql_user=backup
mysql_pwd=wdRyKrQUGLXH4zy6
days_keep=365

# DBs to back up
db_backup[0]=teleconnectdev
db_backup[1]=videosquare

# DB backup directories
db_backup_dirs[0]=/srv/vsq/dev.videosquare.eu/backup/db
db_backup_dirs[1]=/srv/vsq/videosquare.eu/backup/db

date=`date +"%Y%m%d_%H%M%S"`

for i in ${!db_backup[*]}
do

	# Remove backups older than $days_keep
	directory=${db_backup_dirs[${i}]}
	find ${directory} -mtime +$days_keep -exec rm -f {} \; 2> /dev/null

	# mysqldump
	filename=${db_backup[${i}]}_${date}.sql
	/usr/bin/mysqldump -u ${mysql_user} -p"${mysql_pwd}" ${db_backup[${i}]} | gzip -9 > ${directory}/${filename}.gz

done
