#/bin/sh

user="admin"
password="BoRoKaBoGYo1980"

output="/home/conv/wowza_stats.txt"
# Compile: javac JMXCommandLine.java

#Usage:
#
#[command] -[switch [value]...] [command] [params...]
#
#Switches:
#
#  -jmx  [jmx-url]
#  -user [jmx-username]
#  -pass [jmx-password]
#
#Commands:
#
#  getServerVersion
#  startVHost [vhost]
#  stopVHost [vhost]
#  reloadVHostConfig
#  startAppInstance [vhost:application/appInstance]
#  touchAppInstance [vhost:application/appInstance]
#  shutdownAppInstance [vhost:application/appInstance]
#  startMediaCasterStream [vhost:application/appInstance] [stream-name] [mediacaster-type]
#  stopMediaCasterStream [vhost:application/appInstance] [stream-name]
#  resetMediaCasterStream [vhost:application/appInstance] [stream-name]
#  getConnectionCounts
#  getConnectionCounts [vhost:application/appInstance]
#  getConnectionCounts [vhost:application/appInstance] [stream-name]
#  getIOOutByteRate
#  getIOOutByteRate [vhost:application/appInstance]
#  getIOInByteRate
#  getIOInByteRate [vhost:application/appInstance]

#java -cp . JMXCommandLine $@

date=`date +"%Y-%m-%d %T"`

stat=`java -cp . JMXCommandLine -user ${user} -pass ${password} getConnectionCounts`

echo ${date}: ${stat}

echo ${date}: ${stat} >> ${output}

