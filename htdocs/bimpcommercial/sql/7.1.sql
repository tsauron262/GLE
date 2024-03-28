ALTER TABLE `llx_propal` ADD `contrats_status` INT(11) NOT NULL DEFAULT 0;

-- UPDATE llx_propal SET contrats_status = 2 WHERE date_valid < '2023-10-01 00:00:00';