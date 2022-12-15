ALTER TABLE `llx_bf_refinanceur` ADD `id_def_contact_signataire` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande` ADD `contacts_livraisons` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bf_demande` ADD `contrat_signataires_data` TEXT NOT NULL DEFAULT '';