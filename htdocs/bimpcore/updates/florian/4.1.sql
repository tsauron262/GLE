
ALTER TABLE `llx_br_reservation_shipment` DROP `date`;
ALTER TABLE `llx_br_reservation_shipment` ADD `id_commande_client_line` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `llx_br_reservation_shipment` ADD `group_articles` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_br_reservation_shipment` ADD `id_equipment` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `llx_br_reservation_shipment` ADD `id_product` INT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `llx_br_services` RENAME TO llx_br_service;
ALTER TABLE `llx_br_service` ADD `qty` INT UNSIGNED NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `llx_br_service_shipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_commande_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_commande_client_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_service` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_shipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
