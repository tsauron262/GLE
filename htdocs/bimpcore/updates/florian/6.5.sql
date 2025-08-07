ALTER TABLE `llx_commande` ADD `validFin` INT NOT NULL DEFAULT '0'
, ADD `validComm` INT NOT NULL DEFAULT '0' AFTER `validFin`;
