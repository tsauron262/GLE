ALTER TABLE llx_bimp_apple_consigned_stock ADD `serialized` tinyint(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `llx_bimp_apple_consigned_stock_shipment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code_centre` varchar(30) NOT NULL DEFAULT '',
  `order_id` varchar(30) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT 0,
  `carrier_code` varchar(30) NOT NULL DEFAULT '',
  `tracking_number` varchar(60) NOT NULL DEFAULT '',
  `date_submitted` datetime DEFAULT NULL,
  `date_shipped` datetime DEFAULT NULL,
  `shipment_number` varchar(30) NOT NULL DEFAULT '',
  `parts` mediumtext NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `llx_bimp_apple_consigned_stock_mvt` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_stock` int(11) NOT NULL DEFAULT 0,
  `id_user` int(11) NOT NULL DEFAULT 0,
  `date` datetime DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `serial` varchar(255) NOT NULL DEFAULT '',
  `code_mvt` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL DEFAULT '',
  `cancelled` tinyint(1) NOT NULL DEFAULT 0
);

