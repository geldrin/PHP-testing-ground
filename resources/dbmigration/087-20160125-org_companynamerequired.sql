ALTER TABLE `organizations`
ADD `isnicknamehidden` int(11) NOT NULL DEFAULT '0' AFTER `displaynametype`,
ADD `isorganizationaffiliationrequired` int(11) NOT NULL DEFAULT '0' AFTER `isnicknamehidden`;

UPDATE organizations
SET isnicknamehidden = 1
WHERE displaynametype = 'hidenickname';
