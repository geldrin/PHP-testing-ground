
ALTER TABLE `recordings`
ADD `commentsenabled` int not null default '0';

UPDATE recordings SET
commentsenabled = 1
WHERE isanonymouscommentsenabled = 1;
