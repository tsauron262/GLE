
ALTER TABLE `llx_bc_vente_remise` ADD `per_unit` BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE `llx_be_reservation` ADD `origin_id_element` INT NOT NULL DEFAULT '0' AFTER `active`;
ALTER TABLE `llx_be_reservation` ADD `origin_element` INT NOT NULL DEFAULT '0' AFTER `active`;