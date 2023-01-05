ALTER TABLE `llx_be_equipment` ADD `date_immo` DATE NULL DEFAULT NULL AFTER `date_warranty_end`; 

UPDATE `llx_be_equipment` a SET a.`date_immo` = (SELECT p.date FROM llx_be_equipment_place p WHERE p.id_equipment = a.id AND p.type IN (3,9) ORDER BY p.position DESC LIMIT 1);