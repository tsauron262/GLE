ALTER TABLE `llx_bimp_commande_line` ADD `periodicity` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `nb_periods` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `periods_start` DATE NULL DEFAULT NULL;