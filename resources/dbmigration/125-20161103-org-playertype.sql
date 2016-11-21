ALTER TABLE `organizations`
ADD `playertype` text;

UPDATE organizations SET playertype = 'flash' WHERE domain IS NOT NULL AND domain <> '';
