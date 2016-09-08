ALTER TABLE `organizations_directories`
CHANGE `ldapusernameregex` `ldapusernametransformregexp` text COLLATE 'utf8_general_ci' NULL AFTER `name`,
ADD `ldapuserprecheckquery` text COLLATE 'utf8_general_ci' NULL AFTER `ldapusertreedn`;
