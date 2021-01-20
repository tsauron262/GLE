ALTER TABLE `llx_bimp_commande_line` CHANGE `periodicity` `fac_periodicity` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `nb_periods` `fac_nb_periods` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `periods_start` `fac_periods_start` DATE NULL DEFAULT NULL;

ALTER TABLE `llx_bimp_commande_line` ADD `exp_periodicity` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `exp_nb_periods` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `exp_periods_start` DATE NULL DEFAULT NULL;
ALTER TABLE `llx_bimp_commande_line` ADD `next_date_exp` DATE NULL DEFAULT NULL;

ALTER TABLE `llx_bimp_commande_line` ADD `achat_periodicity` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `achat_nb_periods` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `achat_periods_start` DATE NULL DEFAULT NULL;
ALTER TABLE `llx_bimp_commande_line` ADD `next_date_achat` DATE NULL DEFAULT NULL;