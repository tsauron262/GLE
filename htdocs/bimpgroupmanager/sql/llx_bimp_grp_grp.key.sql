ALTER TABLE `llx_bimp_grp_grp` DROP FOREIGN KEY `const_fk_parent`;
ALTER TABLE `llx_bimp_grp_grp` DROP FOREIGN KEY `const_fk_child`;


ALTER TABLE `llx_bimp_grp_grp` ADD CONSTRAINT `const_fk_parent` FOREIGN KEY (`fk_parent`) REFERENCES `llx_usergroup` (`rowid`) ON DELETE CASCADE;
ALTER TABLE `llx_bimp_grp_grp` ADD CONSTRAINT `const_fk_child`  FOREIGN KEY (`fk_child`)  REFERENCES `llx_usergroup` (`rowid`) ON DELETE CASCADE;
