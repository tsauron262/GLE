CREATE TABLE IF NOT EXISTS `llx_Bimp_ImportPaiement` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `banque` int(11) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_Bimp_ImportPaiementLine` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_import` int(11) NOT NULL,
  `data` text NOT NULL DEFAULT '',
  `price` DECIMAL(24,8) NOT NULL DEFAULT 0,
  `type` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL DEFAULT '',
  `factures` varchar(50) NOT NULL DEFAULT '',
  `traite` boolean NOT NULL DEFAULT 0
) ENGINE=InnoDB;
