
ALTER TABLE `llx_br_reservation_shipment` ADD `id_commande_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `llx_br_reservation_shipment` ADD `id_shipment` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `ref_reservation`;

CREATE TABLE IF NOT EXISTS `llx_br_commande_shipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_commande_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `num_livraison` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB;

ALTER TABLE `llx_br_reservation_cmd_fourn` ADD `id_reservation` int(10) UNSIGNED NOT NULL DEFAULT '0';

INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('default_id_client', 0);
INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('default_id_product', 0);