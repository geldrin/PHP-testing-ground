ALTER TABLE  `livestream_profiles` CHANGE  `isdynamic`  `type` TEXT NOT NULL;
ALTER TABLE  `livestream_profiles` ADD  `iscontentenabled` INT( 11 ) NOT NULL DEFAULT  '1' AFTER  `streamsuffix`;
ALTER TABLE  `livestream_profiles` ADD  `streamidlength` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '6' AFTER  `type`;
ALTER TABLE  `livestream_profiles` ADD  `contentstreamidlength` INT( 10 ) UNSIGNED NULL DEFAULT NULL AFTER  `iscontentenabled`;