
ALTER TABLE `organizations`
ADD `ondemandhdsenabled` int not null default '0',
ADD `livehdsenabled` int not null default '0' AFTER `ondemandhdsenabled`,
ADD `ondemandhlsenabledandroid` int not null default '0' AFTER `livehdsenabled`,
ADD `livehlsenabledandroid` int not null default '0' AFTER `ondemandhlsenabledandroid`;
