ALTER TABLE `livefeeds`
ADD `pin` int(10) NULL;

ALTER TABLE `livefeeds`
ADD UNIQUE `uq-pin` (`pin`)

ALTER TABLE `organizations`
ADD `islivepinenabled` int(11) NOT NULL DEFAULT '0';

CREATE TABLE `livefeed_teacherinvites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `livefeedid` int unsigned NOT NULL,
  `pin` int NOT NULL,
  `emails` text NOT NULL,
  `userids` text NOT NULL,
  `timestamp` timestamp NOT NULL
);
