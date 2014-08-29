ALTER TABLE  `cdn_streaming_servers` ADD  `isrtmpcompatible` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `servicetype` ,
ADD  `isrtspcompatible` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `isrtmpcompatible` ,
ADD  `ishdscompatible` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `isrtspcompatible` ,
ADD  `ishlscompatible` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `ishdscompatible`;
