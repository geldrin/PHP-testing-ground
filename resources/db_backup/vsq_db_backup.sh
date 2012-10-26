#!/bin/bash

mysql_user=backup
mysql_pwd=wdRyKrQUGLXH4zy6
days_keep=365

# DBs to back up
db_backup[0]=teleconnectdev
db_backup[1]=videosquare

# DB backup directories
db_backup_dirs[0]=/srv/storage/dev.videosquare.eu/backup/db
db_backup_dirs[1]=/srv/storage/videosquare.eu/backup/db

date=`date +"%Y%m%d_%H%M%S"`
echo "MySQL backup started: ${date}"

for i in ${!db_backup[*]}
do
	echo "Backing up DB: ${db_backup[${i}]}"

	# Remove backups older than $days_keep
	directory=${db_backup_dirs[${i}]}
	echo " Removing backups older than ${days_keep} days from: ${directory}"
#	find ${directory}* -mtime +$days_keep -exec rm -f {} \; 2> /dev/null
	find ${directory}* -mtime +$days_keep

	# mysqldump
	filename=${db_backup[${i}]}_${date}.sql
	echo " Target filename: ${directory}/${filename}"
	/usr/bin/mysqldump -u ${mysql_user} -p"${mysql_pwd}" ${db_backup[${i}]} > /tmp/${filename}

	# targz dump file
	cd /tmp
	tar -cvzf ${directory}/${filename}.tar.gz ${filename}

done
