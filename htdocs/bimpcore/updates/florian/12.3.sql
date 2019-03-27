CREATE TABLE IF NOT EXISTS `llx_bl_commande_fourn_reception` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_commande_fourn` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ref` varchar(128) NOT NULL DEFAULT '',
  `num_reception` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `date_received` datetime DEFAULT NULL,
  `info` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE `llx_bimp_commande_fourn_line` ADD `receptions` TEXT NOT NULL DEFAULT '' AFTER `force_qty_1`;
