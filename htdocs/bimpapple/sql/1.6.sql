ALTER TABLE `llx_bimp_apple_consigned_stock_mvt` RENAME TO `llx_bimp_apple_part_stock_mvt`;
ALTER TABLE `llx_bimp_apple_part_stock_mvt` ADD `stock_type` VARCHAR(10) NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_apple_part_stock_mvt` ADD `code_centre` VARCHAR(10) NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_apple_part_stock_mvt` ADD `part_number` VARCHAR(255) NOT NULL DEFAULT '';

UPDATE `llx_bimp_apple_part_stock_mvt` SET `stock_type` = 'consigned' WHERE 1;
UPDATE `llx_bimp_apple_part_stock_mvt` SET `code_centre` = (SELECT cs.code_centre FROM llx_bimp_apple_consigned_stock cs WHERE cs.id = id_stock);
UPDATE `llx_bimp_apple_part_stock_mvt` SET `part_number` = (SELECT cs.part_number FROM llx_bimp_apple_consigned_stock cs WHERE cs.id = id_stock);