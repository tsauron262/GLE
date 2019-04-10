ALTER TABLE `llx_commande_fournisseur` ADD `fk_user_resp` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande_fournisseur` ADD `attente_info` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_commande_fournisseur` ADD `invoice_status` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_commande` ADD `shipment_status` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande` ADD `invoice_status` INT NOT NULL DEFAULT '0';