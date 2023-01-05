ALTER TABLE `llx_bic_user` CHANGE `attached_societe` `id_client` int NOT NULL DEFAULT 0;
UPDATE `llx_bic_user` SET `status` = 0 WHERE `status` = 2 

ALTER TABLE `llx_bic_user` 
CHANGE `date_create` `date_create` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
CHANGE `user_create` `user_create` INT(11) NOT NULL DEFAULT '0', 
CHANGE `date_update` `date_update` DATETIME NULL DEFAULT CURRENT_TIMESTAMP, 
CHANGE `user_update` `user_update` INT(11) NULL DEFAULT '0'; 

ALTER TABLE `llx_bic_user_contrat` 
CHANGE `date_create` `date_create` DATETIME NULL DEFAULT CURRENT_TIMESTAMP, 
CHANGE `user_create` `user_create` INT(11) NULL DEFAULT '0', 
CHANGE `date_update` `date_update` DATETIME NULL DEFAULT CURRENT_TIMESTAMP, 
CHANGE `user_update` `user_update` INT(11) NULL DEFAULT '0'; 