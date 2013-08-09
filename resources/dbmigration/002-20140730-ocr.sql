ALTER TABLE recordings ADD ocrstatus TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER contentmasterstatus;

CREATE TABLE IF NOT EXISTS ocr_frames (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  recordingid int(11) unsigned NOT NULL,
  positionsec int(10) unsigned NOT NULL,
  ocrtext text,
  status text,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
