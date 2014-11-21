ALTER TABLE  `organizations_contracts` ADD  `isreportenabled` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `currency`;
ALTER TABLE  `organizations_contracts` ADD  `reportemailaddresses` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `isreportenabled`;
