ALTER TABLE `llx_contratdet` ADD `renouvellement` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `llx_contrat` ADD `current_renouvellement` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `llx_contrat` ADD `initial_renouvellement` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `llx_contrat` ADD `syntec_renouvellement` DOUBLE NOT NULL DEFAULT '0';
ALTER TABLE `llx_contrat` ADD `date_end_renouvellement` DATE DEFAULT NULL;
ALTER TABLE `llx_bcontract_prelevement` ADD `renouvellement` INT(11) NOT NULL DEFAULT '0';