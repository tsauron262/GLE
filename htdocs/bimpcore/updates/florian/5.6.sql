
ALTER TABLE `llx_br_commande_shipment` ADD `info` TEXT NOT NULL;
ALTER TABLE `llx_br_commande_shipment` ADD `signed` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_shipped`;
ALTER TABLE `llx_br_commande_shipment` ADD `id_facture` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_shipped`;

ALTER TABLE `llx_commande` ADD `id_facture` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `multicurrency_total_ttc`;
