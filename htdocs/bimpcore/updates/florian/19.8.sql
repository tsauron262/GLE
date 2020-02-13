ALTER TABLE `llx_stock_mouvement` ADD `bimp_origin` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `llx_stock_mouvement` ADD `bimp_id_origin` INT NOT NULL DEFAULT '0'; 

-- UPDATE `llx_stock_mouvement` SET `bimp_origin` = `origintype`, `bimp_id_origin` = `fk_origin` WHERE `fk_origin` > 0;