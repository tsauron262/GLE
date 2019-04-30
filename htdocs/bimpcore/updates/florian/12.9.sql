ALTER TABLE `llx_bimp_commande_line` ADD `remise_pa` DECIMAL(12,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_propal_line` ADD `remise_pa` DECIMAL(12,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bs_sav_propal_line` ADD `remise_pa` DECIMAL(12,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` ADD `remise_pa` DECIMAL(12,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_propal_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bs_sav_propal_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_propal_line` ADD `hide_product_label` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bs_sav_propal_line` ADD `hide_product_label` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_commande_line` ADD `hide_product_label` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_facture_line` ADD `hide_product_label` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `hide_product_label` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_facture_fourn_line` ADD `hide_product_label` TEXT NOT NULL DEFAULT '';
