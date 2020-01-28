
ALTER TABLE `llx_bf_demande` ADD `sites` TEXT DEFAULT NULL;
ALTER TABLE `llx_bf_demande_line` ADD `site` VARCHAR(256) NOT NULL DEFAULT '';

ALTER TABLE `llx_bf_refinanceur` RENAME TO llx_bf_demande_refinanceur;

ALTER TABLE `llx_bf_demande_refinanceur` DROP `name`;
ALTER TABLE `llx_bf_demande_refinanceur` ADD `id_refinanceur` INT NOT NULL DEFAULT '0' AFTER `id`;

CREATE TABLE IF NOT EXISTS `llx_bf_refinanceur` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_societe` int(11) NOT NULL DEFAULT '0'
);