CREATE TABLE IF NOT EXISTS `llx_bimp_product_cur_pa` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_product` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(24,8) NOT NULL DEFAULT 0.00000000,
  `date_from` datetime DEFAULT NULL,
  `date_to` datetime DEFAULT NULL,
  `origin` varchar(255) NOT NULL DEFAULT '',
  `id_origin` int(11) NOT NULL DEFAULT 0,
  `id_fourn_price` int(11) NOT NULL DEFAULT 0
);

ALTER TABLE `llx_bimp_product_cur_pa`
  ADD KEY `product` (`id_product`);

ALTER TABLE `llx_product` ADD `no_fixe_prices` BOOLEAN NOT NULL DEFAULT FALSE;