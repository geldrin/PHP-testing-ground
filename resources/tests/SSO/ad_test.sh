#!/bin/bash

username="hu-svcVideoSquare"
#group_dn="CN=HU-SG VideoSquare Admin,OU=Marketing,OU=Infrastructure,OU=Security Groups,DC=hu,DC=kworld,DC=kpmg,DC=com"
group_dn="CN=HU-SG VideoSquare Access,OU=Marketing,OU=Infrastructure,OU=Security Groups,DC=hu,DC=kworld,DC=kpmg,DC=com"

ad_user_query="sAMAccountName"

echo "-------------------- Service user query -----------------------"

ldapsearch -x -H \
 ldap://hubudgc02.hu.kworld.kpmg.com -b 'DC=hu,DC=kworld,DC=kpmg,DC=com' \
 -D 'CN=hu-svcVideoSquare,OU=IT,OU=Service Accounts,DC=hu,DC=kworld,DC=kpmg,DC=com' \
 -w 'ng8raYcQ5wdIVcbkFCXr' \
 "(&(objectClass=user)(objectCategory=person)(${ad_user_query}=${username}))"

echo "-------------------- Group membership query -----------------------"

ldapsearch -x -H \
 ldap://hubudgc02.hu.kworld.kpmg.com -b 'DC=hu,DC=kworld,DC=kpmg,DC=com' \
 -D 'CN=hu-svcVideoSquare,OU=IT,OU=Service Accounts,DC=hu,DC=kworld,DC=kpmg,DC=com' \
 -w 'ng8raYcQ5wdIVcbkFCXr' \
 "(&(memberOf:1.2.840.113556.1.4.1941:=${group_dn})(objectClass=person)(objectClass=user))" sAMAccountName UserPrincpipalName
