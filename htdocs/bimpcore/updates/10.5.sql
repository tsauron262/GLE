ALTER TABLE `llx_bf_refinanceur` ADD `quantity` INT NOT NULL AFTER `date_update`, ADD `amount_ht` DECIMAL (12,4) NOT NULL AFTER `quantity`, ADD `payment` INT NOT NULL AFTER `amount_ht`, ADD `periodicity` INT NOT NULL AFTER `payment`, ADD `periode2` BOOLEAN NOT NULL AFTER `periodicity`;
ALTER TABLE `llx_bf_demande` ADD `agreement_number` INT DEFAULT NULL;
