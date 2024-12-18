ALTER TABLE llx_bimp_location 
    ADD `fac_date_from` DATE DEFAULT NULL,
    ADD `fac_date_to` DATE DEFAULT NULL,
    ADD `lines_process_status` int(11) NULL DEFAULT 0;

ALTER TABLE llx_bimp_location_line 
    ADD `status` int(11) NOT NULL DEFAULT 0 AFTER id_forfait,
    ADD `fac_date_from` DATE DEFAULT NULL,
    ADD `fac_date_to` DATE DEFAULT NULL;


UPDATE llx_bimp_location_line a
LEFT JOIN llx_bimp_location l ON l.id = a.id_location
SET a.status = 1
WHERE l.status = 1 AND a.cancelled = 0;

UPDATE llx_bimp_location_line a
LEFT JOIN llx_bimp_location l ON l.id = a.id_location
SET a.status = 10
WHERE l.status = 10 AND a.cancelled = 0;

UPDATE llx_bimp_location_line SET `status` = -1 WHERE cancelled = 1;

ALTER TABLE llx_bimp_location_line DROP `cancelled`;