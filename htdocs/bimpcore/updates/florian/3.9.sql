
CREATE TABLE IF NOT EXISTS `llx_br_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_commande_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_commande_client_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `shipped` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;
