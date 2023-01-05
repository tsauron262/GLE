ALTER TABLE `llx_fichinterdet` ADD `techs` LONGTEXT NULL DEFAULT NULL;
ALTER TABLE `llx_fichinterdet` ADD `id_line_commande` INT(11) DEFAULT NULL;
ALTER TABLE `llx_fichinterdet` ADD `id_line_contrat` INT(11) DEFAULT NULL;
ALTER TABLE `llx_fichinterdet` ADD `type` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `llx_fichinterdet` ADD `statut` INT(11) NOT NULL DEFAULT '0';