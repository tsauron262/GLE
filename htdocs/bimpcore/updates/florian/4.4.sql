
ALTER TABLE `llx_bs_sav` ADD `id_facture_acompte` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_propal`;

CREATE TABLE IF NOT EXISTS `llx_bs_apple_part` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `label` varchar(256) NOT NULL DEFAULT '',
  `part_number` varchar(128) NOT NULL DEFAULT '',
  `comptia_code` varchar(128) NOT NULL DEFAULT '',
  `comptia_modifier` varchar(128) NOT NULL DEFAULT '',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `component_code` varchar(128) NOT NULL DEFAULT '',
  `stock_price` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;