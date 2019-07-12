ALTER TABLE `llx_entrepot` ADD `ship_to` VARCHAR(255) DEFAULT '';
ALTER TABLE `llx_product` ADD `cur_pa_ht` DOUBLE(24,8) NOT NULL DEFAULT '0' AFTER `pmp`;  