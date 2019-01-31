
ALTER TABLE `llx_bf_demande_line` ADD `product_type` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `type`;
ALTER TABLE `llx_bf_demande_line` ADD `extra_serials` TEXT NOT NULL DEFAULT '' AFTER `equipments`;