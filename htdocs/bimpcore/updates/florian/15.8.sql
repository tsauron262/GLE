ALTER TABLE `llx_product` ADD `cur_pa_id_origin` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `cur_pa_ht`;
ALTER TABLE `llx_product` ADD `cur_pa_origin` VARCHAR(255) NOT NULL DEFAULT '' AFTER `cur_pa_ht`;

