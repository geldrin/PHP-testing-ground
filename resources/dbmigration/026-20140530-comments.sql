ALTER TABLE  `comments`
ADD  `replyto` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `id`,
ADD  `sequenceid` INT UNSIGNED NOT NULL DEFAULT  '1' AFTER  `id`,
ADD UNIQUE  `uq_recordingid_sequenceid` (  `recordingid` ,  `sequenceid` );

ALTER TABLE  `recordings` ADD  `notifyaboutcomments` INT NOT NULL DEFAULT  '1';
