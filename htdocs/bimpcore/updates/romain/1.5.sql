-- Définition d'une date_update par défault
ALTER TABLE `llx_bl_inventory` CHANGE `date_update` `date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP; 

-- Ajout inventaire parent
ALTER TABLE `llx_bl_inventory` ADD `parent` INT NULL DEFAULT NULL AFTER `date_update`; 
ALTER TABLE `llx_bl_inventory` CHANGE `parent` `parent` INT(11) NULL DEFAULT '0'; 