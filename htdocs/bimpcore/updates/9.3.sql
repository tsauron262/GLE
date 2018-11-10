ALTER TABLE `llx_bf_demande` DROP `id_facture_frais`;
ALTER TABLE `llx_bf_demande` DROP `id_facture_loyer_intercalaire`;
ALTER TABLE `llx_bf_frais_divers` ADD `id_facture` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bf_rent_except` ADD `id_facture` INT NOT NULL DEFAULT '0';