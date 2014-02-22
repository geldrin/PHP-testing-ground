ALTER TABLE  `livefeeds` ADD  `anonymousallowed` int not null default '0' AFTER  `accesstype`;
ALTER TABLE  `livefeed_chat` ADD  `anonymoususer` TEXT NULL AFTER  `userid`;
