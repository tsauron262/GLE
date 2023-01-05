ALTER TABLE `llx_propal` ADD `replaced_ref` VARCHAR(255) NOT NULL DEFAULT ''; 
ALTER TABLE `llx_commande` ADD `replaced_ref` VARCHAR(255) NOT NULL DEFAULT ''; 
ALTER TABLE `llx_facture` ADD `replaced_ref` VARCHAR(255) NOT NULL DEFAULT ''; 
ALTER TABLE `llx_commande_fournisseur` ADD `replaced_ref` VARCHAR(255) NOT NULL DEFAULT ''; 
ALTER TABLE `llx_facture_fourn` ADD `replaced_ref` VARCHAR(255) NOT NULL DEFAULT ''; 