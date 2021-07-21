ALTER TABLE `llx_fichinter` ADD `fk_user_tech` INT NOT NULL DEFAULT '0' AFTER `fk_user_author`, 
ALTER TABLE `llx_fichinter` ADD `time_from` TIME NULL DEFAULT NULL AFTER `fk_user_tech` AFTER `datei`, 
ALTER TABLE `llx_fichinter` ADD `time_to` TIME NULL DEFAULT NULL AFTER `time_from` AFTER `time_from`; 