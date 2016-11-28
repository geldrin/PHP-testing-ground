ALTER TABLE `organizations`
CHANGE `playertype` `ondemandplayertype` text NULL AFTER `tokenverifyurl`;

ALTER TABLE `organizations`
ADD `liveplayertype` text;

UPDATE organizations SET liveplayertype = 'flash' WHERE domain IS NOT NULL AND domain <> '';
