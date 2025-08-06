
ALTER TABLE `llx_br_reservation` ADD `id_origin` INT NOT NULL DEFAULT '0' AFTER `note`;
ALTER TABLE `llx_br_reservation` ADD `origin` VARCHAR(256) NOT NULL DEFAULT '' AFTER `note`;
