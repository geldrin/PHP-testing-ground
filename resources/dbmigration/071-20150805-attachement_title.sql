UPDATE attached_documents SET title = masterfilename WHERE title = '' OR title IS NULL;
ALTER TABLE attached_documents CHANGE `title`  `title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
