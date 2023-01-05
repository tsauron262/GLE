
ALTER TABLE `llx_bimp_commande_line` DROP `qty_shipped`;

ALTER TABLE `llx_bimp_commande_line` ADD `ref_reservations` VARCHAR(128) NOT NULL DEFAULT '' AFTER `remise`;
ALTER TABLE `llx_bimp_commande_line` ADD `factures` TEXT NOT NULL DEFAULT '' AFTER `remise`;
ALTER TABLE `llx_bimp_commande_line` ADD `shipments` TEXT NOT NULL DEFAULT '' AFTER `remise`;
