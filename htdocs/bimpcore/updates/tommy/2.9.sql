
CREATE TABLE IF NOT EXISTS `llx_Bimp_PaiementInc` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `fk_soc` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `total` float NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
