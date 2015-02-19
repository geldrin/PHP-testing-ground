ALTER TABLE  `organizations` ADD  `layoutcss` TEXT NULL ,
ADD  `layoutwysywygcss` TEXT NULL ,
ADD  `layoutheader` TEXT NULL ,
ADD  `layoutfooter` TEXT NULL ,
ADD  `lastmodifiedtimestamp` DATETIME NULL;

UPDATE `organizations`
SET layoutcss = CONCAT('a, #header a, #header .submitbutton { color: #', linkcolor, ';}')
WHERE LENGTH(linkcolor) > 0;

ALTER TABLE  `organizations` DROP  `linkcolor`;
