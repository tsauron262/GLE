
ALTER TABLE `llx_bimp_gsx_repair` CHANGE `new_serial` `repair_confirm_number` VARCHAR(128) NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_gsx_repair` CHANGE `serial_update_confirm_number` `new_serial` VARCHAR(128) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS `llx_bnf_frais` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date` date DEFAULT NULL,
  `description` text NOT NULL,
  `status` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `ticket_validated` tinyint(1) NOT NULL DEFAULT '0',
  `period` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bnf_frais_montant` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_frais` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tva_tx` float NOT NULL DEFAULT '0',
  `amount` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bnf_frais_kilometers` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_frais` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `chevaux` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `carburant` INT NOT NULL DEFAULT '0';
  `kilometers` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bnf_period` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL
) ENGINE=InnoDB;

ALTER TABLE `llx_bs_sav` CHANGE `system` `system` INT(11) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `llx_bs_sav` ADD `extra_infos` TEXT NOT NULL DEFAULT '' AFTER `resolution`;