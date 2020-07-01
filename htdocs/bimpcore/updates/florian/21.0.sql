ALTER TABLE `llx_societe` ADD `solvabilite_status` INT NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_facture` CHANGE `nb_relance` `nb_relance` INT(11) NOT NULL DEFAULT '0'; 

INSERT INTO `llx_bimpcore_conf` (`name`, `value`) VALUES ('relance_paiements_extented_delay_type_ent', '5');
