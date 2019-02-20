
ALTER TABLE `llx_br_commande_shipment` DROP `qty`;
ALTER TABLE `llx_br_commande_shipment` ADD `id_contact` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `num_livraison`;
ALTER TABLE `llx_br_commande_shipment` ADD `status` INT UNSIGNED NOT NULL DEFAULT '1' AFTER `num_livraison`;
ALTER TABLE `llx_br_commande_shipment` CHANGE `date` `date_shipped` DATETIME NULL DEFAULT NULL;

ALTER TABLE `llx_br_commande_shipment` ADD `date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `date_shipped`;
ALTER TABLE `llx_br_commande_shipment` ADD `user_update` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_shipped`;
ALTER TABLE `llx_br_commande_shipment` ADD `date_create` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `date_shipped`; 
ALTER TABLE `llx_br_commande_shipment` ADD `user_create` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_shipped`;

ALTER TABLE `llx_br_reservation_shipment` DROP `date`;
ALTER TABLE `llx_br_reservation_shipment` ADD `id_commande_client_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_commande_client`;
ALTER TABLE `llx_br_reservation_shipment` ADD `group_articles` BOOLEAN NOT NULL DEFAULT FALSE AFTER `id_user`;
ALTER TABLE `llx_br_reservation_shipment` ADD `id_equipment` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_user`;
ALTER TABLE `llx_br_reservation_shipment` ADD `id_product` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_user`;

ALTER TABLE `llx_br_services` RENAME TO llx_br_service;
ALTER TABLE `llx_br_service` ADD `qty` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_commande_client_line`;

CREATE TABLE IF NOT EXISTS `llx_br_service_shipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_commande_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_commande_client_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_service` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_shipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;