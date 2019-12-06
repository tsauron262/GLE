CREATE TABLE `llx_bcontract_serials` ( `id` INT NOT NULL AUTO_INCREMENT , `id_contrat` INT NOT NULL , `id_line` text DEFAULT NULL,  `serial` VARCHAR(255) NOT NULL , `imei` VARCHAR(255) NOT NULL , 
`date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
id_equipment int(11) DeFAULT NULL,
PRIMARY KEY (`id`)) ENGINE = InnoDB;