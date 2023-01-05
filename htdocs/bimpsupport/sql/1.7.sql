CREATE TABLE `llx_bs_remote_token` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`id_user` INT NOT NULL , 
`user_create` INT NOT NULL , 
`user_update` INT NOT NULL , 
`id_client` INT NOT NULL , 
`token` text(30) NOT NULL default '' , 
`port` int NOT NULL default 0 , 
`date_valid` DATETIME NOT NULL , 
`date_create` DATETIME NOT NULL , 
`date_update` DATETIME NOT NULL , 

PRIMARY KEY (`id`)) ENGINE = InnoDB;