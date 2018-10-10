
ALTER TABLE `llx_bimp_file` ADD `user_delete` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_update`;
ALTER TABLE `llx_bimp_file` ADD `date_delete` DATETIME NULL DEFAULT NULL AFTER `date_update`;
ALTER TABLE `llx_bimp_file` ADD `deleted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `date_update`;

ALTER TABLE `llx_bimp_propal_line` CHANGE `remise` `remise` FLOAT(24,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_propal_line` ADD `remisable` BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE `llx_bimp_facture_line` ADD `remisable` BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE `llx_bimp_commande_line` ADD `remisable` BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE `llx_bs_sav_propal_line` ADD `remisable` BOOLEAN NOT NULL DEFAULT TRUE;

ALTER TABLE `llx_bc_vente_remise` CHANGE `percent` `percent` FLOAT(24,6) NOT NULL DEFAULT '0', CHANGE `montant` `montant` FLOAT(24,2) NOT NULL DEFAULT '0';

ALTER TABLE `llx_propal` ADD `remise_globale` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_facture` ADD `remise_globale` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande` ADD `remise_globale` DOUBLE(24,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_object_line_remise` CHANGE `percent` `percent` FLOAT(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_propal_line` ADD UNIQUE( `id_line`);