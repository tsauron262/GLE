ALTER TABLE `llx_bf_demande` ADD `no_fac_fourn` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande` ADD `no_fac_fin` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande` ADD `id_facture_fourn_rev` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande` ADD `id_facture_cli_rev` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande` ADD `total_rachat_ht` double(24,8) NOT NULL DEFAULT 0;

ALTER TABLE `llx_bf_demande` CHANGE `date_loyer` `date_loyer` DATE DEFAULT NULL;