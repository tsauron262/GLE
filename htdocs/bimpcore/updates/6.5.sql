ALTER TABLE `llx_commande` ADD `validFin` INT NOT NULL DEFAULT '0' AFTER `id_facture`
, ADD `validComm` INT NOT NULL DEFAULT '0' AFTER `validFin`;