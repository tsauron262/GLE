

CREATE TABLE `llx_en_cmd_infos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cmd` int(11) NOT NULL,
  `id_cmd_parent` int(11) NOT NULL,
  `role` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

/*
A chaque MAJ
*/
ALTER TABLE `llx_en_historyArch` ADD `id` INT  NOT NULL  AUTO_INCREMENT  AFTER `value` , ADD INDEX (`id`);