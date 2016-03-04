ALTER TABLE `organizations`
CHANGE `frontpageblockorder` `indextemplate` text COLLATE 'utf8_general_ci' NULL AFTER `signupvalidationemailsubject_stringid`;

UPDATE organizations SET indextemplate = NULL;
