ALTER TABLE  `organizations` CHANGE  `viewsessiontimeouthours`  `viewsessiontimeoutminutes` INT( 11 ) NOT NULL DEFAULT  '300';
UPDATE `organizations` SET viewsessiontimeoutminutes = viewsessiontimeoutminutes * 60;
