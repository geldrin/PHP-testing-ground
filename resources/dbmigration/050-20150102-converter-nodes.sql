ALTER TABLE  `converter_nodes` ADD  `status` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `default`,
ADD  `type` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `shortname`,
ADD  `storagesystemtotal` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `status`,
ADD  `storagesystemfree` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storagesystemtotal`,
ADD  `storagetotal` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storagesystemfree`,
ADD  `storageworktotal` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storagesystemfree`,
ADD  `storageworkfree` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storageworktotal`,
ADD  `storagefree` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storagetotal`,
ADD  `cpuusage` FLOAT UNSIGNED NULL DEFAULT NULL AFTER  `storagefree`;

RENAME TABLE  `converter_nodes` TO  `infrastructure_nodes`;

UPDATE infrastructure_nodes SET TYPE =  'converter' WHERE id >= 1;
