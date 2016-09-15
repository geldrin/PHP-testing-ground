ALTER TABLE `livefeeds`
ADD `istokenrequired` int NOT NULL DEFAULT '0' AFTER `accesstype`;

ALTER TABLE `recordings`
ADD `istokenrequired` int NOT NULL DEFAULT '0' AFTER `accesstype`;
