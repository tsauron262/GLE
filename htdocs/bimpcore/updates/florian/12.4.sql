
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bimp_commande_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bimp_facture_fourn_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bimp_facture_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bimp_propal_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bs_sav_propal_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;