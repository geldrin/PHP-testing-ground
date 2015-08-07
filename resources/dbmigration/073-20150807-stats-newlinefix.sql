UPDATE view_statistics_live
SET useragent = REPLACE(REPLACE(useragent, '\r', ' '), '\n', ' ');

UPDATE view_statistics_ondemand
SET useragent = REPLACE(REPLACE(useragent, '\r', ' '), '\n', ' ');
