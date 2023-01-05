ALTER TABLE `llx_societe` ADD `solvabilite_status` INT NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_facture` CHANGE `nb_relance` `nb_relance` INT(11) NOT NULL DEFAULT '0';
