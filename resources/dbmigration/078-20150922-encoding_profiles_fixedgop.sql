ALTER TABLE  `encoding_profiles` ADD  `fixedgopenabled` INT( 10 ) UNSIGNED NULL DEFAULT  '0' AFTER  `videobpp` ,
ADD  `fixedgoplengthms` INT( 10 ) UNSIGNED NULL DEFAULT NULL AFTER  `fixedgopenabled`;

UPDATE `encoding_profiles` SET `fixedgopenabled` = 1, `fixedgoplengthms` = 4000 WHERE `mediatype` = 'video';
