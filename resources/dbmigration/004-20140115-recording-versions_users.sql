ALTER TABLE users ADD firstloggedin DATETIME NULL DEFAULT NULL AFTER timestampdisabledafter;
ALTER TABLE recordings ADD smilstatus TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER ocrstatus;
ALTER TABLE users ADD isusergenerated INT UNSIGNED NULL DEFAULT '0' AFTER validationcode;

CREATE TABLE IF NOT EXISTS recordings_versions (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  recordingid int(10) unsigned NOT NULL,
  name text NOT NULL,
  filename text NOT NULL,
  iscontent int(10) unsigned NOT NULL DEFAULT '0',
  status text NOT NULL,
  resolution text,
  bandwidth int(10) unsigned DEFAULT NULL,
  isdesktopcompatible int(10) unsigned DEFAULT NULL,
  ismobilecompatible int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;