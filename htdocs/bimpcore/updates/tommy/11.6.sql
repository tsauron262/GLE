CREATE TABLE IF NOT EXISTS llx_usergroup_revue (
	  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `id_group` int(10) UNSIGNED NOT NULL DEFAULT '0',
	  `fk_user` INT NOT NULL DEFAULT '0',
	  `data_revue` TEXT NULL DEFAULT NULL,
	  entity INT default 1
) ENGINE=InnoDB;

INSERT into llx_usergroup_revue (SELECT null, u.rowid,fk_user, data_revue,1 FROM llx_usergroup u, llx_usergroup_extrafields ue WHERE ue.fk_object = u.rowid);
