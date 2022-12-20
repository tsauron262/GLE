CREATE TABLE IF NOT EXISTS `llx_product_remise_arriere` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_product` int(11) NOT NULL DEFAULT 0,
  `type` varchar(30) NOT NULL DEFAULT '',
  `nom` varchar(255) NOT NULL DEFAULT '',
  `value` DECIMAL(24,6) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  KEY `id_product` (`id_product`)
)