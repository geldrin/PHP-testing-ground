-- userquery:
-- a default: (&(objectCategory=person)(objectClass=user)(sAMAccountName=%USERNAME%))
-- vagy:
-- (&(objectClass=user)(objectclass=person)(userPrincipalName=%USERNAME%))
-- lehetseges valtozok amiket helyetesitunk, alapbol mindig escapelve:
-- %USERNAME% %UNESCAPED_USERNAME% (username mindig a @ elotti account)
-- %ACCOUNTNAME% %UNESCAPED_ACCOUNTNAME% (a teljes @-al egyutti account)
--
-- usergroupquery: (nem frontend dolog, nem mi kezeljuk)
-- (&(memberOf:1.2.840.113556.1.4.1941:=%GROUP_DN%)(objectClass=person)(objectClass=user))
--
-- usernameregex (muszaj legyen egy (?<username>barmi) named capture benne):
-- ha nem kell a domain, default: /^(?<username>.+)@.*$/
-- ha kell a domain is: /^(?<username>.+)$/

ALTER TABLE `organizations_directories`
ADD `ldapuserquery` text COLLATE 'utf8_general_ci' NULL AFTER `name`,
ADD `ldapusergroupquery` text COLLATE 'utf8_general_ci' NULL AFTER `ldapuserquery`,
ADD `ldapusernameregex` text COLLATE 'utf8_general_ci' NULL AFTER `name`;
