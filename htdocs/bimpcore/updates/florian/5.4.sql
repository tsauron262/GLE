
ALTER TABLE `llx_bimp_gsx_repair` CHANGE `new_serial` `repair_confirm_number` VARCHAR(128) NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_gsx_repair` CHANGE `serial_update_confirm_number` `new_serial` VARCHAR(128) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS `llx_bnf_frais` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date` date DEFAULT NULL,
  `description` text NOT NULL,
  `status` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `ticket_validated` tinyint(1) NOT NULL DEFAULT '0',
  `period` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bnf_frais_montant` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_frais` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tva_tx` float NOT NULL DEFAULT '0',
  `amount` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bnf_frais_kilometers` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_frais` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `chevaux` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `carburant` INT NOT NULL DEFAULT '0',
  `kilometers` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bnf_period` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL
) ENGINE=InnoDB;

ALTER TABLE `llx_bnf_period` ADD `status` INT(2) NOT NULL DEFAULT 0;
UPDATE `llx_extrafields` SET `type` = 'sellist', `size` = '', `param` = 'a:1:{s:7:\"options\";a:1:{s:45:\"Synopsis_contrat_annexePdf:modeleName:id::1=1\";N;}}' WHERE `llx_extrafields`.`rowid` = 27;

ALTER TABLE `llx_bimpcore_file` ADD `visibility` INT(11) NOT NULL DEFAULT '2';
ALTER TABLE `llx_bc_caisse` ADD `compte_comptable` INT(11) DEFAULT NULL;
ALTER TABLE `llx_facture` ADD `exported` INT(2) DEFAULT 0;

ALTER TABLE `llx_entrepot` ADD `code_journal_compta` VARCHAR(3) NOT NULL;
ALTER TABLE `llx_entrepot` ADD `compte_comptable_banque` VARCHAR(11) NOT NULL;
ALTER TABLE `llx_entrepot` ADD `compte_comptable` VARCHAR(11) NOT NULL;
ALTER TABLE `llx_entrepot` ADD `compte_aux` VARCHAR(100) NOT NULL;
ALTER TABLE `llx_facture_fourn` ADD `exported` INT(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_societe` ADD `exported` INT(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_paiement` ADD `exported` INT(10) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bank_account` ADD `cegid_journal` VARCHAR(3) DEFAULT NULL;
ALTER TABLE `llx_bank_account` ADD `compte_compta` VARCHAR(20) DEFAULT NULL;

ALTER TABLE `llx_contratdet` ADD `serials` LONGTEXT NULL DEFAULT NULL;

ALTER TABLE `llx_societe_rib` ADD `exported` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE llx_contrat ADD tmp_correct INT(1) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimpcore_conf` ADD `module` VARCHAR(30) NOT NULL DEFAULT 'bimpcore' AFTER `id`;


UPDATE `llx_bimpcore_conf` SET `name` = 'vente_dee_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_dee_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_dee_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_dee_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_dee_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_dee_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_dee_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_dee_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_produit_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_produit_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_service_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_service_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_produit_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_produit_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_service_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_service_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_produit_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_produit_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_service_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_service_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_produit_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_produit_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_service_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_service_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_produit_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_produit_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_service_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_service_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_produit_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_produit_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_service_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_service_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'autoliquidation_tva_666', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_autoliquidation_tva_666' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'autoliquidation_tva_711', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_autoliquidation_tva_711' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_tva_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_tva_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_tva_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_tva_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'vente_tva_null', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_vente_tva_null' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_null', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_null' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_null_service', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_null_service' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_01', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_01' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_02', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_02' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_03', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_03' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_04', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_04' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_05', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_05' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_06', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_06' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_07', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_07' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_08', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_08' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_09', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_09' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_10', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_10' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_11', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_11' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_tva_france_12', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_tva_france_12' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'avoir_fournisseur_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_avoir_fournisseur_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'avoir_fournisseur_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_avoir_fournisseur_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'avoir_fournisseur_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_avoir_fournisseur_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'rfa_fournisseur_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_rfa_fournisseur_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'rfa_fournisseur_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_rfa_fournisseur_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'rfa_fournisseur_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_rfa_fournisseur_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'achat_fournisseur_apple', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_achat_fournisseur_apple' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'avoir_fournisseur_apple', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_avoir_fournisseur_apple' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'rfa_fournisseur_apple', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_rfa_fournisseur_apple' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'frais_de_port_achat_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_frais_de_port_achat_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'frais_de_port_achat_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_frais_de_port_achat_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'frais_de_port_achat_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_frais_de_port_achat_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'frais_de_port_vente_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_frais_de_port_vente_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'frais_de_port_vente_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_frais_de_port_vente_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'frais_de_port_vente_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_frais_de_port_vente_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'comissions_fr', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_comissions_fr' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'comissions_ue', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_comissions_ue' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'comissions_ex', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_comissions_ex' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'refacturation_ht', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_refacturation_ht' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'refacturation_ttc', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_refacturation_ttc' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'code_fournisseur_apple', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_code_fournisseur_apple' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'start_current_trimestre', `module` = 'bimptocegid' WHERE `name` = 'BIMPtoCEGID_start_current_trimestre' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'file_entity', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_file_entity' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'version_tra', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_version_tra' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'last_export_date', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_last_export_date' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'minimum_date_export_pay', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_minimum_date_export_pay' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'comptes_importPaiement', `module` = 'bimptocegid' WHERE `name` = 'BIMPTOCEGID_comptes_importPaiement' AND `module` = 'bimpcore';

