

CREATE TABLE IF NOT EXISTS `llx_bimpcore_correctif` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_type` VARCHAR(128) NOT NULL DEFAULT '',
  `obj_module` VARCHAR(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `id_obj` int(11) NOT NULL DEFAULT '0',
  `field` varchar(128) NOT NULL,
  `value` FLOAT NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  `id_user` int(11) NOT NULL DEFAULT '0',
  `done` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

ALTER TABLE `llx_commande` ADD `logistique_status` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande` ADD `id_user_resp` INT NOT NULL DEFAULT '0';