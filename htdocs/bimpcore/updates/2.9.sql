
ALTER TABLE `llx_bmp_event` DROP `date_end`;
ALTER TABLE `llx_bmp_event` CHANGE `date_begin` `date` DATETIME NULL DEFAULT NULL;
ALTER TABLE `llx_bmp_event` ADD `analytics` VARCHAR(128) NOT NULL DEFAULT '' AFTER `status`; 
ALTER TABLE `llx_bmp_event` ADD `tva_billets` INT NOT NULL DEFAULT '1' AFTER `ca_moyen_bar`;

ALTER TABLE `llx_bmp_event_tarif` ADD `droits_loc_coprods` FLOAT NOT NULL DEFAULT '0' AFTER `previsionnel`;
ALTER TABLE `llx_bmp_event_tarif` ADD `droits_loc` FLOAT NOT NULL DEFAULT '0' AFTER `previsionnel`;