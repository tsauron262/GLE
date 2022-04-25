CREATE TABLE `llx_mvt_paiement` 
( 
    `id` INT NOT NULL AUTO_INCREMENT ,
    `datas` TEXT DEFAULT '{}', 
    `date` DATE,
    `traite` INT(11) DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;