
DROP TABLE llx_bs_pret;

ALTER TABLE `llx_be_equipment_place` ADD `code_centre` VARCHAR(12) NOT NULL DEFAULT '' AFTER `id_entrepot`; 

ALTER TABLE `llx_bs_sav_pret` ADD `code_centre` VARCHAR(12) NOT NULL DEFAULT '' AFTER `id_sav`;
ALTER TABLE `llx_bs_sav_pret` CHANGE `id_pret` `id_equipment` INT(10) UNSIGNED NOT NULL DEFAULT '0';