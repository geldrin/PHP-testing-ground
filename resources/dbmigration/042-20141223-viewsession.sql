ALTER TABLE  `organizations` ADD  `viewsessiontimeouthours` INT NOT NULL DEFAULT  '5',
ADD  `viewsessionallowedextraseconds` INT NOT NULL DEFAULT  '360';
