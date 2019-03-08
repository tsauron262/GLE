



CREATE TABLE IF NOT EXISTS `llx_bmp_event_group` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(256) NOT NULL,
  `rank` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bmp_event_billets` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_soc_seller` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `seller_name` varchar(256) NOT NULL DEFAULT '',
  `id_tarif` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `quantity` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bmp_event_montant_detail` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_event_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `label` varchar(256) NOT NULL DEFAULT '',
  `quantity` int(11) NOT NULL DEFAULT '1',
  `unit_price` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


ALTER TABLE `llx_bmp_event` ADD `status` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_end`;
ALTER TABLE `llx_bmp_event` ADD `place` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_end`; 
ALTER TABLE `llx_bmp_event` ADD `type` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_end`;

ALTER TABLE `llx_bmp_event_montant` CHANGE `confirmed` `status` INT(11) NOT NULL DEFAULT '1';
ALTER TABLE `llx_bmp_event_montant` ADD `comment` TEXT NOT NULL DEFAULT '' AFTER `type`;

ALTER TABLE `llx_bmp_type_montant` ADD `code_compta` VARCHAR(128) NOT NULL DEFAULT '' AFTER `id_taxe`;

