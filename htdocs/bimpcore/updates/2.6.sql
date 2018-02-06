
CREATE TABLE IF NOT EXISTS `llx_be_logiciel` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `use_product` tinyint(1) NOT NULL DEFAULT '1',
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(256) NOT NULL DEFAULT '',
  `id_fournisseur` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `version` varchar(128) NOT NULL DEFAULT '',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `id_user_account` INT UNSIGNED NOT NULL DEFAULT '0',
  `sn` varchar(128) NOT NULL DEFAULT '',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `llx_be_user_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL DEFAULT ''
  `login` varchar(128) NOT NULL DEFAULT '',
  `pword` varchar(128) NOT NULL DEFAULT '',
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `llx_be_connection` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(256) NOT NULL,
  `type` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `geometry` varchar(128) NOT NULL DEFAULT '',
  `color_depth` varchar(128) NOT NULL,
  `full_screen` tinyint(1) NOT NULL DEFAULT '1',
  `console` tinyint(1) NOT NULL DEFAULT '1',
  `share_disk` tinyint(1) NOT NULL DEFAULT '1',
  `port` int(10) UNSIGNED NOT NULL,
  `port_redir` varchar(128) NOT NULL DEFAULT '',
  `font_size` int(10) UNSIGNED NOT NULL,
  `id_user_account` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `llx_be_reseau` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(256) NOT NULL DEFAULT '',
  `ip` varchar(128) NOT NULL,
  `mask` varchar(128) NOT NULL,
  `gateway` varchar(128) NOT NULL,
  `comment` text NOT NULL,
  `id_connection` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `llx_bmp_event_tarif` DROP `ca_moyen_bar`;
ALTER TABLE `llx_bmp_event` ADD `ca_moyen_bar` FLOAT NOT NULL DEFAULT '0' AFTER `status`;
ALTER TABLE `llx_bmp_event_group` ADD `number` INT NOT NULL DEFAULT '0' AFTER `name`;

CREATE TABLE IF NOT EXISTS `llx_bmp_montant_detail_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_type_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `label` varchar(256) NOT NULL,
  `use_groupe_number` tinyint(1) NOT NULL DEFAULT '0',
  `unit_price` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;