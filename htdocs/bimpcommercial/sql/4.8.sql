CREATE TABLE `llx_Bimp_Apporteur` ( `id` INT NOT NULL AUTO_INCREMENT , `id_fourn` INT NOT NULL, 
`date_create` datetime ,
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime,
  `user_update` int(10) unsigned NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE = InnoDB;
CREATE TABLE `llx_Bimp_ApporteurFilter` ( `id` INT NOT NULL AUTO_INCREMENT , `id_rapporteur` INT NOT NULL, `filter` VARCHAR(1000), `commition` DECIMAL(24,4) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE = InnoDB;
CREATE TABLE `llx_Bimp_CommissionApporteur` ( `id` INT NOT NULL AUTO_INCREMENT , `id_apporteur` INT NOT NULL, 
`total` DECIMAL(24,8) NOT NULL DEFAULT '0', 
  `status` INT NOT NULL DEFAULT '0',
`date_create` datetime ,
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime,
  `user_update` int(10) unsigned NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE = InnoDB;

ALTER TABLE `llx_bimp_facture_line` ADD `commission_apporteur` VARCHAR(100) DEFAULT '0';
