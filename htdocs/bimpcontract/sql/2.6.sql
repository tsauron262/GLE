CREATE TABLE `llx_bcontract_avenant` 
    ( 
        `id` INT(11) NOT NULL AUTO_INCREMENT , 
        `id_contrat` INT(11) NOT NULL ,
        `number_in_contrat` INT(11) NOT NULL DEFAULT '0',
        `lieu` VARCHAR(255) DEFAULT NULL,
        `date_effect` DATE DEFAULT NULL,
        `date_signed` DATE DEFAULT NULL,
        `signed` INT(11) NOT NULL DEFAULT '0' ,
        `statut` INT(11) NOT NULL DEFAULT '0' ,
        `user_create` int(10) unsigned NOT NULL DEFAULT 0,
        `date_create` datetime,
        `user_update` int(10) unsigned NOT NULL DEFAULT 0,
        `date_update` datetime, PRIMARY KEY (`id`)
    ) 
ENGINE = InnoDB;
CREATE TABLE `llx_bcontract_avenantdet` 
    ( 
        `id` INT(11) NOT NULL AUTO_INCREMENT , 
        `in_contrat` INT(11) NOT NULL DEFAULT '1',
        `id_avenant` INT(11) NOT NULL ,
        `id_line_contrat` INT(11) DEFAULT NULL,
        `id_serv` INT(11) DEFAULT NULL,
        `serials_in` TEXT DEFAULT NULL,
        `serials_out` TEXT DEFAULT NULL,
        `remise` FLOAT DEFAULT NULL ,
        `ht` FLOAT DEFAULT NULL ,
        `qty` INT(11) DEFAULT NULL,
        `description` TEXT DEFAULT NULL, 
        `user_create` int(10) unsigned NOT NULL DEFAULT 0,
        `date_create` datetime,
        `user_update` int(10) unsigned NOT NULL DEFAULT 0,
        `date_update` datetime, PRIMARY KEY (`id`)
    ) 
ENGINE = InnoDB;