ALTER TABLE `organizations`
ADD `signupvalidationemailsubject` text COLLATE 'utf8_general_ci' NULL;
ALTER TABLE `organizations`
ADD `signupvalidationemailsubject_stringid` int(10) unsigned NULL;
