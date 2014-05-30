UPDATE `channels` SET accesstype = 'public' WHERE accesstype = '' AND ispublic = 1;
UPDATE `channels` SET accesstype = 'registrations' WHERE accesstype = '' AND ispublic = 0;
ALTER TABLE  `channels` DROP  `ispublic`;

