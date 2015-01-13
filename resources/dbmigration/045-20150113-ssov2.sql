ALTER TABLE  `groups` CHANGE  `directoryid`  `organizationdirectoryid` INT( 10 ) NULL DEFAULT NULL ,
CHANGE  `directorygroupobjectname`  `organizationdirectoryldapdn` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE  `directorygroupwhenchanged`  `organizationdirectoryldapwhenchanged` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
