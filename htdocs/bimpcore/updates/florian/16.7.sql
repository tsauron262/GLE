ALTER TABLE `llx_bimp_commande_line` CHANGE `shipments` `shipments` LONGTEXT  NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_commande_line` CHANGE `factures` `factures` LONGTEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_commande_line` CHANGE `equipments_returned` `equipments_returned` LONGTEXT NOT NULL DEFAULT '';