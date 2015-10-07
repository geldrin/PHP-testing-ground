ALTER TABLE  `users_invitations` CHANGE  `name`  `namefirst` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE  `users_invitations` ADD  `namelast` TEXT NULL AFTER  `namefirst`;
