ALTER TABLE `livefeeds`
ADD `pin` int(10) NULL;

ALTER TABLE `livefeeds`
ADD UNIQUE `uq-pin` (`pin`)

ALTER TABLE `organizations`
ADD `islivepinenabled` int(11) NOT NULL DEFAULT '0';

ALTER TABLE `users_invitations`
ADD `livefeedteacheremails` blob NULL;

ALTER TABLE `users_invitations`
ADD `livefeedteacheruserids` blob NULL;
