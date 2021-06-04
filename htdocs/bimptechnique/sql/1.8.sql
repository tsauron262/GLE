CREATE TABLE `llx_fichinter_facturation` 
( 
    `id` INT NOT NULL AUTO_INCREMENT ,
    `fk_fichinter` INT NOT NULL , 
    `id_commande` INT NOT NULL,
    `fi_lines` TEXT NOT NULL , 
    `id_commande_line` INT NOT NULL,
    `is_vendu` INT NOT NULL , 
    `id_facture` INT(11) DEFAULT NULL , 
    `total_ht_vendu` DOUBLE NOT NULL , 
    `tva_tx` DOUBLE NOT NULL , 
    `total_ht_depacement` DOUBLE NOT NULL , 
    `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
    `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
    `remise` DOUBLE NOT NULL , PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE `llx_fichinter` ADD `type_signature`  INT(11) NOT NULL DEFAULT '0';