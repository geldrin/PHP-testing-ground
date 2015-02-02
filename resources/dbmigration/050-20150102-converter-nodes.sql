ALTER TABLE  `converter_nodes` ADD  `status` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `default`,
ADD  `storagesystemtotal` BIGINT( 20 ) UNSIGNED NULL AFTER  `status`,
ADD  `storagesystemfree` BIGINT( 20 ) UNSIGNED NULL AFTER  `storagesystemtotal`,
ADD  `storagetemptotal` BIGINT( 20 ) UNSIGNED NULL AFTER  `storagesystemfree`,
ADD  `storagetempfree` BIGINT( 20 ) UNSIGNED NULL AFTER  `storagetemptotal`,
ADD  `cpuusage` FLOAT UNSIGNED NULL DEFAULT NULL AFTER  `storagetempfree`;
