ALTER TABLE `llx_bimp_relance_clients_line` ADD `method` INT NOT NULL DEFAULT '0';

UPDATE `llx_bimp_relance_clients_line` SET method = 2 WHERE relance_idx <= 3;
UPDATE `llx_bimp_relance_clients_line` SET method = 1 WHERE relance_idx < 5;