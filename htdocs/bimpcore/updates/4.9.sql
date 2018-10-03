
CREATE TABLE IF NOT EXISTS `llx_gsx_comptia` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` varchar(24) NOT NULL DEFAULT '',
  `grp` varchar(24) DEFAULT NULL,
  `code` varchar(24) NOT NULL DEFAULT '',
  `label` varchar(256) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

ALTER TABLE `llx_bimp_gsx_repair` ADD `total_from_order_changed` BOOLEAN NOT NULL DEFAULT FALSE AFTER `date_closed`;

ALTER TABLE `llx_bs_sav` ADD `acompte` FLOAT NOT NULL DEFAULT '0' AFTER `pword_admin`;
ALTER TABLE `llx_bs_sav` DROP `cover`;

ALTER TABLE `llx_bs_sav_product` ADD `id_reservation` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`;
ALTER TABLE `llx_bs_sav_product` ADD `id_equipment` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`; 

ALTER TABLE `llx_br_reservation` ADD `id_sav_product` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_transfert`;
ALTER TABLE `llx_br_reservation` ADD `id_sav` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_transfert`;

CREATE TABLE IF NOT EXISTS `llx_bs_pret` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ref` varchar(128) NOT NULL DEFAULT '',
  `serial` varchar(128) NOT NULL DEFAULT '',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `code_centre` varchar(128) NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bs_sav_pret` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sav` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_pret` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ref` varchar(128) NOT NULL DEFAULT '',
  `id_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_begin` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_end` datetime DEFAULT NULL,
  `returned` tinyint(1) NOT NULL DEFAULT '0',
  `user_create` int(11) NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;