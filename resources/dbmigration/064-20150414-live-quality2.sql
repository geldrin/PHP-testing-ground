ALTER TABLE  `livefeed_streams` DROP  `quality`;
ALTER TABLE  `livefeed_streams` CHANGE  `name`  `qualitytag` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
