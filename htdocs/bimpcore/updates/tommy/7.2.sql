ALTER TABLE `llx_product` ADD `type_remise_arr` tinyint(1) NOT NULL DEFAULT '0';
UPDATE llx_product SET type_remise_arr = 1 WHERE rowid IN (SELECT fk_object FROM llx_product_extrafields WHERE crt > 0);
