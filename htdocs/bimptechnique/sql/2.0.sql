ALTER TABLE `llx_fichinter` ADD `fk_user_tech` INT NOT NULL DEFAULT '0' AFTER `fk_user_author`;
ALTER TABLE `llx_fichinter` ADD `time_from` TIME NULL DEFAULT NULL; 
ALTER TABLE `llx_fichinter` ADD `time_to` TIME NULL DEFAULT NULL; 

ALTER TABLE `llx_fichinterdet` ADD `id_dol_line_commande` INT NOT NULL DEFAULT '0' AFTER `id_line_commande`;
ALTER TABLE `llx_fichinter_facturation` ADD `id_dol_line_commande` INT NOT NULL DEFAULT '0' AFTER `id_commande_line`;