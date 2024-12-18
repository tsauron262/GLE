CREATE TABLE IF NOT EXISTS `llx_bimp_product_price_rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_product` int(11) NOT NULL DEFAULT 0,
  `type_condition` varchar(30) NOT NULL DEFAULT '',
  `cond_value` DECIMAL(24,6) NOT NULL DEFAULT 0,
  `type_impact` varchar(30) NOT NULL DEFAULT '',
  `impact_value` DECIMAL(24,6) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  KEY `id_product` (`id_product`)
);

ALTER TABLE llx_product DROP `min_qty`;