ALTER TABLE  `organizations` CHANGE  `defaultencodingprofilegroupid`  `defaultencodingprofilegroupid` INT( 10 ) UNSIGNED NULL DEFAULT  '4';

UPDATE `organizations` SET `defaultencodingprofilegroupid` = 4 WHERE 1;