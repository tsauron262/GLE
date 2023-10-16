INSERT INTO llx_usergroup_user  SELECT null, 2, fk_user, fk_usergroup FROM llx_usergroup_user WHERE entity = 1;

INSERT INTO `llx_const` SELECT null, `name`, 2, `value`, `type`, `visible`, `note`, `tms` FROM llx_const WHERE name LIKE '%ldap%';


UPDATE llx_user SET entity = 0 WHERE admin = 1;