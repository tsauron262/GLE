

ALTER TABLE `llx_propal` ADD `remise_globale_label` TEXT NULL DEFAULT NULL;
ALTER TABLE `llx_commande` ADD `remise_globale_label` TEXT NULL DEFAULT NULL;
ALTER TABLE `llx_facture` ADD `remise_globale_label` TEXT NULL DEFAULT NULL;

ALTER TABLE `llx_bimp_commande_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_facture_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0'; 
