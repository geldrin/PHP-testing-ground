
ALTER TABLE `users`
ADD `organizationaffiliation` text NULL AFTER `nameformat`;

ALTER TABLE `users`
CHANGE `nickname` `nickname` text NULL AFTER `externalid`;

ALTER TABLE `organizations`
ADD `displaynametype` text NULL AFTER `fullnames`;

UPDATE organizations
SET displaynametype = 'shownickname'
WHERE fullnames = 0;

UPDATE organizations
SET displaynametype = 'showfullname'
WHERE fullnames = 1;

ALTER TABLE `organizations`
DROP `fullnames`;
