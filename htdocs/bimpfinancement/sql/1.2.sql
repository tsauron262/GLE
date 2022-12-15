ALTER TABLE `llx_bf_demande` ADD `pvr_status` int(11) NOT NULL DEFAULT 0 AFTER `contrat_status`;
ALTER TABLE `llx_bf_demande` ADD `id_facture_fourn` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande` ADD `id_facture_fin` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande` ADD `pvr_signataires_data` TEXT NOT NULL DEFAULT '' AFTER `signature_pvr_params`;
