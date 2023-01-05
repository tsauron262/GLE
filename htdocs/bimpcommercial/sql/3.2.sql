ALTER TABLE `llx_bimp_commande_line` ADD `periods_start` DATE NULL DEFAULT NULL;
ALTER TABLE `llx_bimp_commande_line` ADD `next_date_facture` DATE NULL DEFAULT NULL;
ALTER TABLE `llx_bimp_commande_line` ADD `fact_echue` boolean DEFAULT 0;

