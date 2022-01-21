CREATE TABLE IF NOT EXISTS `llx_Bimp_ImportPrelevement` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `banque` int(11) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_Bimp_ImportPrelevementLine` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `data` text NOT NULL DEFAULT '',
  `id_import` int(11) NOT NULL,
  `price` DECIMAL(24,8) NOT NULL DEFAULT 0,
  `facture` int(50) NOT NULL DEFAULT 0,
  `traite` boolean NOT NULL DEFAULT 0,
  `date` DATE NULL DEFAULT NULL
) ENGINE=InnoDB;
