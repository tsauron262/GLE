
CREATE TABLE IF NOT EXISTS `llx_bmp_calc_montant` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(256) NOT NULL,
  `type_source` INT NOT NULL DEFAULT '1',
  `id_montant_source` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_total_source` INT UNSIGNED NOT NULL DEFAULT '0',
  `source_amount` float NOT NULL DEFAULT '0',
  `id_target` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `percent` float NOT NULL DEFAULT '0',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `required` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `llx_bmp_total_inter` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY (`id`),
  `name` varchar(256) NOT NULL DEFAULT '',
  `all_frais` BOOLEAN NOT NULL DEFAULT FALSE,
  `all_recettes` BOOLEAN NOT NULL DEFAULT FALSE,
  `display` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

`all_frais` BOOLEAN NOT NULL DEFAULT FALSE, `all_recettes` BOOLEAN NOT NULL DEFAULT FALSE,

CREATE TABLE IF NOT EXISTS `llx_bmp_event_calc_montant` (
  `id` INT NOT NULL AUTO_INCREMENT ADD PRIMARY KEY,
  `id_event` int(11) NOT NULL,
  `id_calc_montant` int(11) NOT NULL,
  `percent` float NOT NULL,
  `source_amount` FLOAT NOT NULL DEFAULT '0',
  `active` BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `llx_bmp_event_tarif` ADD `previsionnel` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `llx_bmp_event_tarif` ADD `ca_moyen_bar` FLOAT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bmp_event` ADD `bar_20_save` FLOAT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bmp_event` ADD `bar_55_save` FLOAT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bmp_categorie_montant` ADD `color` VARCHAR(128) NOT NULL;
ALTER TABLE `llx_bmp_categorie_montant` ADD `position` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bmp_event_montant` ADD `id_coprod` INT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `llx_bmp_type_montant` ADD `coprod` BOOLEAN NOT NULL DEFAULT TRUE;

ALTER TABLE `llx_bimp_objects_associations` ADD `association` VARCHAR(128) NOT NULL DEFAULT '' AFTER `id`;