ALTER TABLE `llx_be_equipment` ADD `origin_id_element` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `reserved`;
ALTER TABLE `llx_be_equipment` ADD `origin_element` VARCHAR(256) NOT NULL DEFAULT '' AFTER `reserved`;
ALTER TABLE `llx_be_equipment` ADD `prix_achat` FLOAT NOT NULL DEFAULT '0.00' AFTER `reserved`;

ALTER TABLE `llx_be_equipment_place` ADD `code_mvt` VARCHAR(128) NOT NULL DEFAULT '' AFTER `date`;

CREATE TABLE IF NOT EXISTS `llx_be_reservation` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_user_origin` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_equipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `date_from` datetime DEFAULT NULL,
  `date_to` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bc_vente_remise` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_vente` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `label` varchar(256) NOT NULL DEFAULT '',
  `id_article` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `percent` float NOT NULL DEFAULT '0',
  `montant` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bc_caisse_mvt` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_entrepot` INT UNSIGNED NOT NULL DEFAULT '0',
  `id_caisse` INT UNSIGNED NOT NULL DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `montant` float NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;