ALTER TABLE  `livefeed_streams` ADD  `quality_temp` TEXT NULL ,
ADD  `weight` INT NOT NULL DEFAULT  '100';

UPDATE `livefeed_streams`
SET quality_temp = 'Normál'
WHERE quality = 0;

UPDATE `livefeed_streams`
SET quality_temp = 'HD'
WHERE quality = 1;

ALTER TABLE  `livefeed_streams` DROP  `quality`;
ALTER TABLE  `livefeed_streams` CHANGE  `quality_temp`  `quality` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
