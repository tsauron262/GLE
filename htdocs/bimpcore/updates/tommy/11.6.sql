CREATE TABLE IF NOT EXISTS llx_usergroup_revue (
	  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `id_group` int(10) UNSIGNED NOT NULL DEFAULT '0',
	  `fk_user` INT NOT NULL DEFAULT '0',
	  `data_revue` TEXT NULL DEFAULT NULL,
	  entity INT default 1
) ENGINE=InnoDB;

ALTER table llx_usergroup ADD COLUMN IF NOT EXISTS fk_user int DEFAULT 0;
