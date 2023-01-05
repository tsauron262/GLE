ALTER TABLE llx_synopsisapple_shipment RENAME TO llx_bimp_gsx_shipment;
ALTER TABLE llx_synopsisapple_shipment_parts RENAME TO llx_bimp_gsx_shipment_part;

ALTER TABLE `llx_bimp_gsx_shipment` ADD `carrier_code` VARCHAR(255) NOT NULL DEFAULT '' AFTER `ship_to`; 
ALTER TABLE `llx_bimp_gsx_shipment` ADD `status` INT NOT NULL DEFAULT '0' AFTER `ship_to`; 
UPDATE `llx_bimp_gsx_shipment` SET `status` = 3 WHERE 1;

ALTER TABLE `llx_bimp_gsx_shipment` ADD `gsx_note` TEXT NOT NULL DEFAULT '' AFTER `weight`; 

ALTER TABLE `llx_bimp_gsx_shipment` ADD `user_create` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_gsx_shipment` ADD `date_create` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `llx_bimp_gsx_shipment` ADD `user_update` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_gsx_shipment` ADD `date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `llx_bimp_gsx_shipment_part` CHANGE `name` `name` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_gsx_shipment_part` CHANGE `part_number` `part_number` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_gsx_shipment_part` CHANGE `part_new_number` `part_new_number` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `llx_bimp_gsx_shipment_part` CHANGE `part_po_number` `part_po_number` VARCHAR(255) NOT NULL DEFAULT ''; 
ALTER TABLE `llx_bimp_gsx_shipment_part` CHANGE `repair_number` `repair_number` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_gsx_shipment_part` CHANGE `serial` `serial` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_gsx_shipment_part` CHANGE `return_order_number` `return_order_number` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `llx_bimp_gsx_shipment_part` CHANGE `expected_return` `expected_return` DATE NULL DEFAULT NULL;

ALTER TABLE `llx_bimp_gsx_shipment_part` ADD `sequence_number` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_gsx_shipment_part` ADD `pack_number` INT NOT NULL DEFAULT '1';
ALTER TABLE `llx_bimp_gsx_shipment_part` ADD `return_type` VARCHAR(255) NOT NULL DEFAULT '';