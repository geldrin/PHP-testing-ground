ALTER TABLE users ADD avatarsourceip TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER apiaddresses
ALTER TABLE cdn_streaming_servers ADD serverstatus TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'reachable, unreachable' AFTER priority
ALTER TABLE cdn_streaming_servers ADD currentload INT NULL DEFAULT NULL AFTER serverstatus
