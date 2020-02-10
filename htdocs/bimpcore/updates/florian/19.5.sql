ALTER TABLE `llx_be_equipment_place` ADD `id_origin` INT NOT NULL DEFAULT '0' AFTER `code_mvt`; 
ALTER TABLE `llx_be_equipment_place` ADD `origin` VARCHAR(255) NOT NULL DEFAULT '' AFTER `code_mvt`;
ALTER TABLE `llx_be_package_place` ADD `id_origin` INT NOT NULL DEFAULT '0' AFTER `code_mvt`;
ALTER TABLE `llx_be_package_place` ADD `origin` VARCHAR(255) NOT NULL DEFAULT '' AFTER `code_mvt`;
