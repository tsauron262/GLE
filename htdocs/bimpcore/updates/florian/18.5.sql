ALTER TABLE `llx_be_equipment` ADD `meid` VARCHAR(255) NOT NULL DEFAULT '' AFTER `imei`; 
ALTER TABLE `llx_be_equipment` ADD `imei2` VARCHAR(255) NOT NULL DEFAULT '' AFTER `imei`;