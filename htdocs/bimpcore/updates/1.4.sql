ALTER TABLE `llx_bf_rent` ADD `periodicity` INT UNSIGNED NOT NULL DEFAULT '1' AFTER `payment`;
ALTER TABLE `llx_bf_refinanceur` ADD `rate` FLOAT NOT NULL DEFAULT '0' AFTER `status`;
ALTER TABLE ADD `coef` FLOAT NOT NULL DEFAULT '0' AFTER `rate`;