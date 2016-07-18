ALTER TABLE `privileges`
ADD UNIQUE `uq-name` (`name`(50));

ALTER TABLE `userroles_privileges`
ADD INDEX `ix-privilegeid` (`privilegeid`);

INSERT INTO `privileges` (`id`, `name`, `comment`) VALUES
(6,	'general_manageOrganizationObjects',	'regi iseditor vagy isclientadmin, altalaban az elso lepes amit majd utana meg szukitunk ha kell, egy framework szintu altalanos dolog'),
(7,	'general_accessDepartmentOrGroupObjects',	'regi iseditor, hozzaferes a department or group restricted objektumokhoz (channels/recordings/live)'),
(8,	'general_ignoreAccessRestrictions',	'regi isclientadmin vagy isadmin, konkret jogok amiket ad:\r\n- channels/recordings/livefeeds hozzaferes barmifele hozzaferesi beallitasok ellenere\r\n- csoport hozzaférés, csoportbol törlés\r\n- file letoltes (handlefile)'),
(9,	'recordings_modifyrecordingasuser',	'api, csak ezzel a joggal rendelkezo userek hasznalhatjak'),
(10,	'recordings_checkfileresumeasuser',	'api, csak ezzel a joggal rendelkezo userek hasznalhatjak'),
(11,	'recordings_uploadchunkasuser',	'api, csak ezzel a joggal rendelkezo userek hasznalhatjak'),
(12,	'recordings_listallrecordings',	'minden felvetel listazasa, nem csak a sajatoke'),
(13,	'recordings_feature',	'letrehozhat kiemelt felveteleket'),
(14,	'recordings_approveduploader',	'jova hagyhatja a sajat feltoltott felveteleit a user'),
(15,	'recordings_moderateduploader',	'nem hagyhatja jova a sajat feltoltott felveteleit a user'),
(16,	'recordings_createintrooutro',	'letrehozhat intro/outro felveteleket'),
(17,	'users_setuserfield',	'api, csak ezzel a joggal rendelkezo userek hasznalhatjak'),
(18,	'users_globallogin',	'NAGYON VESZELYES - a user organizationjet lecsereljuk a domain organizationjere, es barmelyik domainbe belephet, regi isadmin\r\n'),
(19,	'users_ignoresinglelogin',	'ha letezik akkor a user bejelentkezhet egynel tobbszor mindig'),
(20,	'live_forcemediaserver',	'live/view media server testeles'),
(21,	'live_moderatechat',	'a live chatet moderalhatja'),
(22,	'live_feature',	'letrehozni/kijelolni featured eventeket'),
(23,	'live_ignoreeventend',	'ha az esemeny mar lejart akkor a user tovabbra is hozzafer'),
(24,	'live_search',	'kereshetoek a kozvetitesek'),
(25,	'organizations_newsadmin',	'organizations_newsadmin -- organizations/listnews-nal admin linkeket megjeleniti, es hasznalhatja\r\n'),
(26,	'groups_visible',	'minden csoport latszik e a usernek vagy csak a sajatjai'),
(27,	'groups_deleteanyuser',	'barmilyen usert tud torolni barmilyen csoportbol (adminoknak)'),
(28,	'groups_remotegroups',	'csoportot letrehozni/modositani ami ldap alapu'),
(29,	'contributors_modifyanycontributor',	'barmilyen contributort modosithat a user nem csak a sajat maga altal letrehozottakat'),
(30,	'channels_listallchannels',	'channels/mychannels alatt minden csatorna listazasa nem csak a user altal letrehozottak');
