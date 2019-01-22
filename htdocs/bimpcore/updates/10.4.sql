ALTER TABLE `llx_bmp_event_montant` ADD `paiements` TEXT NOT NULL DEFAULT '' AFTER `id_coprod`;
ALTER TABLE `llx_bmp_event_montant` ADD `tva_tx` FLOAT NOT NULL DEFAULT '0' AFTER `amount`;

ALTER TABLE `llx_bmp_event` ADD `default_dl_prod` DECIMAL(24,2) NOT NULL DEFAULT '0' AFTER `tva_billets`;
ALTER TABLE `llx_bmp_event` ADD `default_dl_dist` DECIMAL(24,2) NOT NULL DEFAULT '0' AFTER `tva_billets`;
ALTER TABLE `llx_bmp_event` ADD `frais_billet` FLOAT NOT NULL DEFAULT '0.2' AFTER `tva_billets`;

ALTER TABLE `llx_bmp_event_billets` ADD `id_coprod` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `quantity`;
ALTER TABLE `llx_bmp_event_billets` ADD `dl_prod` DECIMAL(24,2) NOT NULL DEFAULT '0' AFTER `quantity`;
ALTER TABLE `llx_bmp_event_billets` ADD `dl_dist` DECIMAL(24,2) NOT NULL DEFAULT '0' AFTER `quantity`;
