
CREATE TABLE IF NOT EXISTS `llx_gsx_comptia` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` varchar(24) NOT NULL DEFAULT '',
  `grp` varchar(24) DEFAULT NULL,
  `code` varchar(24) NOT NULL DEFAULT '',
  `label` varchar(256) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

ALTER TABLE `llx_bimp_gsx_repair` ADD `total_from_order_changed` BOOLEAN NOT NULL DEFAULT FALSE AFTER `date_closed`;


ALTER TABLE `llx_br_reservation` ADD `id_sav_product` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_transfert`;
ALTER TABLE `llx_br_reservation` ADD `id_sav` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_transfert`;
