
CREATE TABLE IF NOT EXISTS `llx_bmp_event` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(128) NOT NULL,
  `date_begin` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `llx_bmp_categorie_montant` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `llx_bmp_type_montant` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(128) NOT NULL,
  `id_category` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `id_taxe` INT NOT NULL DEFAULT '11'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `llx_bmp_event_tarif` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `amount` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `llx_bmp_event_coprod` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_soc` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `default_frais_part` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `llx_bmp_event_montant` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_category_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `amount` float NOT NULL DEFAULT '0',
  `confirmed` BOOLEAN NOT NULL DEFAULT FALSE,
  `type` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `llx_bmp_event_coprod_part` (
  `id_event_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_coprod` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `part` float UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `llx_bmp_event_coprod_def_part` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_category_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_event_coprod` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `part` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;