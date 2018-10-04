
CREATE TABLE IF NOT EXISTS `llx_br_order_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_commande` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_order_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty_shipped` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty_billed` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty_returned` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

ALTER TABLE `llx_br_service_shipment` ADD `id_br_order_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_service`;