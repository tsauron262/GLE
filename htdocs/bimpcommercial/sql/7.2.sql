ALTER TABLE `llx_propal` ADD id_demande_fin int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_commande` ADD id_demande_fin int(11) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `llx_bimpcomm_demande_fin` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_module` VARCHAR(255) NOT NULL DEFAULT '',
  `obj_name` VARCHAR(255) NOT NULL DEFAULT '',
  `id_obj` int(11) NOT NULL DEFAULT 0,
  `target` VARCHAR(255) NOT NULL DEFAULT '',
  `id_ext_df` int(11) NOT NULL DEFAULT 0,
  `ref_ext_df` VARCHAR(255) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT 0,
  `id_contact_suivi` int(11) NOT NULL DEFAULT 0,
  `id_contact_signature` int(11) NOT NULL DEFAULT 0,
  `contacts_livraisons` text NOT NULL DEFAULT '',
  `devis_fin_status` int(11) NOT NULL DEFAULT 0,
  `contrat_fin_status` int(11) NOT NULL DEFAULT 0,
  `id_signature_devis_fin` int(11) NOT NULL DEFAULT 0,
  `signature_df_params` text NOT NULL DEFAULT '',
  `id_signature_contrat_fin` int(11) NOT NULL DEFAULT 0,
  `signature_cf_params` text NOT NULL DEFAULT '',
  `signataires_cf_data` text NOT NULL DEFAULT '',
  `serials_ok` tinyint(1) NOT NULL DEFAULT 0,
  `pvr_fin_status` int(11) NOT NULL DEFAULT 0,
  `id_signature_pvr_fin` int(11) NOT NULL DEFAULT 0,
  `signature_pvr_params` TEXT NOT NULL DEFAULT '',
  `signataires_pvr_data` TEXT NOT NULL DEFAULT '',
  UNIQUE KEY `parent` (`obj_module`, `obj_name`, `id_obj`),
  UNIQUE KEY `demande` (`obj_module`,`obj_name`,`id_obj`,`target`,`id_ext_df`),
  KEY `id_signature_devis_fin` (`id_signature_devis_fin`),
  KEY `id_signature_contrat_fin` (`id_signature_contrat_fin`)
);