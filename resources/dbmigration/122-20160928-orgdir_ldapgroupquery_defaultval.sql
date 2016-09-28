UPDATE organizations_directories SET ldapusergroupquery = "(&(memberOf:1.2.840.113556.1.4.1941:=%GROUP_DN%)(objectClass=person)(objectClass=user))" WHERE 1;
