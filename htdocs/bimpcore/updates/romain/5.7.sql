CREATE TABLE IF NOT EXISTS `llx_bimpalert_produit` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `filtre_produit` VARCHAR(1000) NOT NULL ,
  `type_piece` SMALLINT NOT NULL , 
  `filtre_piece` VARCHAR(1000) NOT NULL , 
  `id_user_notif` INT NOT NULL , 
  `message_notif` TEXT NOT NULL DEFAULT '', 
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;