CREATE TABLE IF NOT EXISTS `llx_bs_sac` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ref` varchar(128) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


ALTER TABLE `llx_bs_sav` ADD `sacs` TEXT NOT NULL DEFAULT ''; 