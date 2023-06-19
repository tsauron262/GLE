ALTER TABLE `llx_bimp_product_ldlc` CHANGE `price` `pa_ht` DOUBLE(8,2) NOT NULL; 

ALTER TABLE `llx_bimp_product_ldlc` ADD `tva_tx` DECIMAL(24,8) NOT NULL DEFAULT '0' AFTER `codeLdlc`; 
ALTER TABLE `llx_bimp_product_ldlc` ADD `pu_ht` DECIMAL(24,8) NOT NULL DEFAULT '0' AFTER `codeLdlc`; 

alter table llx_bimp_product_ldlc rename to llx_bimp_product_import_fourn;
ALTER TABLE `llx_bimp_product_import_fourn` ADD `id_fourn` INT NOT NULL;