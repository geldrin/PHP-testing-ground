UPDATE encoding_profiles SET disabled = 1 WHERE shortname = '180p';

UPDATE livestream_profiles SET disabled = 1 WHERE qualitytag LIKE '%180p%';

UPDATE encoding_groups SET `default` = '0' WHERE id = 1;

UPDATE encoding_groups SET `default` = '1' WHERE id = 4;

UPDATE encoding_groups SET disabled =  '1' WHERE id = 2;

UPDATE encoding_groups SET disabled =  '1' WHERE id = 3;

UPDATE organizations SET defaultencodingprofilegroupid = 4 WHERE domain IS NOT NULL;
