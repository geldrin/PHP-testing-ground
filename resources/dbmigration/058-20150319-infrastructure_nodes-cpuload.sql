ALTER TABLE  `infrastructure_nodes` ADD  `cpuloadmin` FLOAT UNSIGNED NULL DEFAULT NULL AFTER  `cpuusage` ,
ADD  `cpuload5min` FLOAT UNSIGNED NULL DEFAULT NULL AFTER  `cpuloadmin` ,
ADD  `cpuload15min` FLOAT UNSIGNED NULL DEFAULT NULL AFTER  `cpuload5min`;
