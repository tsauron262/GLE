CREATE TABLE IF NOT EXISTS `llx_bl_commande_fourn_reception` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_commande_fourn` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ref` varchar(128) NOT NULL DEFAULT '',
  `num_reception` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `date_received` datetime DEFAULT NULL,
  `info` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE `llx_bimp_commande_fourn_line` ADD `receptions` TEXT NOT NULL DEFAULT '' AFTER `force_qty_1`;

ALTER TABLE `llx_commande_fournisseur` ADD `fk_user_resp` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande_fournisseur` ADD `attente_info` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_commande_fournisseur` ADD `invoice_status` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_commande` ADD `shipment_status` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande` ADD `invoice_status` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_be_equipment` ADD `achat_tva_tx` DECIMAL(24,3) NULL DEFAULT NULL AFTER `prix_achat`;
ALTER TABLE `llx_be_equipment` ADD `vente_tva_tx` DECIMAL(24,3) NULL DEFAULT NULL AFTER `prix_vente_except`; 

ALTER TABLE `llx_bl_commande_fourn_reception` ADD `assign_lines_to_commandes_client` BOOLEAN NOT NULL DEFAULT TRUE AFTER `info`;
ALTER TABLE `llx_bimp_commande_line` ADD `qty_modif` DECIMAL(24,3) NOT NULL DEFAULT '0' AFTER `force_qty_1`; 
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_modif` DECIMAL(24,3) NOT NULL DEFAULT '0' AFTER `force_qty_1`;  

CREATE TABLE IF NOT EXISTS `llx_bimpcore_correctif` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_type` VARCHAR(128) NOT NULL DEFAULT '',
  `obj_module` VARCHAR(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `id_obj` int(11) NOT NULL DEFAULT '0',
  `field` varchar(128) NOT NULL,
  `value` FLOAT NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  `id_user` int(11) NOT NULL DEFAULT '0',
  `done` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

ALTER TABLE `llx_commande` ADD `logistique_status` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande` ADD `id_user_resp` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_line` ADD `remise_pa` DECIMAL(12,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_propal_line` ADD `remise_pa` DECIMAL(12,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bs_sav_propal_line` ADD `remise_pa` DECIMAL(12,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` ADD `remise_pa` DECIMAL(12,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_propal_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bs_sav_propal_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_propal_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bs_sav_propal_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_commande_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_facture_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_commande_fourn_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_facture_fourn_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0'; 