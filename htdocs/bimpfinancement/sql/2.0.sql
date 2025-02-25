ALTER TABLE `llx_bf_demande`
	ADD `ref_devis_source` varchar(255) NOT NULL DEFAULT '',
 	ADD `type_contrat` VARCHAR(10) NOT NULL DEFAULT '' AFTER `contrat_signataires_data`;
