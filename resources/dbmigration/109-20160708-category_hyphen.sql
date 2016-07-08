-- !!! futtatas elott a httpdocs/fix/categorynames.php futtatni kell !!!
ALTER TABLE `categories`
DROP `namehyphenated`,
DROP `namehyphenated_stringid`;
