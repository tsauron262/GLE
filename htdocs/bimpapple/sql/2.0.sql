CREATE TABLE IF NOT EXISTS `llx_bimp_apple_stock_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` varchar(30) NOT NULL DEFAULT 'internal',
  `code_centre` varchar(30) NOT NULL DEFAULT '',
  `order_id` varchar(30) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT 0,
  `date_order` datetime DEFAULT NULL,
  `parts` mediumtext NOT NULL DEFAULT '',
  `user_create` INT NOT NULL DEFAULT '0',
  `date_create` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` INT NOT NULL DEFAULT '0',
  `date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);