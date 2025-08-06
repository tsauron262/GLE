

ALTER TABLE `llx_be_equipment` ADD `achat_tva_tx` DECIMAL(24,3) NULL DEFAULT NULL AFTER `prix_achat`;
ALTER TABLE `llx_be_equipment` ADD `vente_tva_tx` DECIMAL(24,3) NULL DEFAULT NULL AFTER `prix_vente_except`;

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

