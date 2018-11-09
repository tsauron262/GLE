
ALTER TABLE `llx_bimp_note` ADD `viewed` INT NOT NULL DEFAULT '1' AFTER `id_obj`;
ALTER TABLE `llx_bimp_note` ADD `email` VARCHAR(256) NOT NULL DEFAULT '' AFTER `id_obj`;
ALTER TABLE `llx_bimp_note` ADD `id_societe` INT NOT NULL DEFAULT '0' AFTER `id_obj`;
ALTER TABLE `llx_bimp_note` ADD `type_author` INT NOT NULL DEFAULT '1' AFTER `id_obj`;
ALTER TABLE `llx_bimp_note` ADD `viewed` BOOLEAN NOT NULL DEFAULT FALSE AFTER `content`;

