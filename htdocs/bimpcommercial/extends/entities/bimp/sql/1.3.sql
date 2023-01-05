ALTER TABLE `llx_bimpcomm_demande_fin` ADD `serials_ok` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bimpcomm_demande_fin` ADD `pvr_fin_status` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bimpcomm_demande_fin` ADD `id_signature_pvr_fin` int(11) NOT NULL DEFAULT 0;

ALTER TABLE `llx_bimpcomm_demande_fin` ADD `signature_pvr_params` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bimpcomm_demande_fin` ADD `signataires_pvr_data` TEXT NOT NULL DEFAULT '';
