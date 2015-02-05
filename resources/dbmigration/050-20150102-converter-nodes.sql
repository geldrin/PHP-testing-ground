RENAME TABLE  `converter_nodes` TO  `infrastructure_nodes`;

ALTER TABLE  `infrastructure_nodes`
ADD  `type` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `shortname`,
ADD  `statusstorage` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `default`,
ADD  `statusnetwork` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `statusstorage`,
ADD  `storagesystemtotal` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `statusnetwork`,
ADD  `storagesystemfree` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storagesystemtotal`,
ADD  `storageworktotal` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storagesystemfree`,
ADD  `storageworkfree` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storageworktotal`,
ADD  `storagetotal` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storageworkfree`,
ADD  `storagefree` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL AFTER  `storagetotal`,
ADD  `cpuusage` FLOAT UNSIGNED NULL DEFAULT NULL AFTER  `storagefree`;

UPDATE infrastructure_nodes SET TYPE =  'converter' WHERE id >= 1;
