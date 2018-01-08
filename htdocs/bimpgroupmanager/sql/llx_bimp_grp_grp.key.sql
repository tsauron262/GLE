ALTER TABLE `llx_bimp_grp_grp` ADD CONSTRAINT `const_fk_parent` FOREIGN KEY (`fk_parent`) REFERENCES `llx_usergroup` (`rowid`);
ALTER TABLE `llx_bimp_grp_grp` ADD CONSTRAINT `const_fk_child`  FOREIGN KEY (`fk_child`) REFERENCES  `llx_usergroup` (`rowid`);
