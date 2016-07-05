ALTER TABLE  `livestream_groups` ADD  `isadaptive` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `feedtype`;

ALTER TABLE  `encoding_groups` ADD  `isadaptive` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `islegacy`;
