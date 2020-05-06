ALTER TABLE `llx_fichinter` ADD `logs` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_fichinter` ADD `techs` LONGTEXT NULL DEFAULT NULL;
ALTER TABLE `llx_fichinter` ADD `commandes` LONGTEXT NULL DEFAULT NULL;
ALTER TABLE `llx_fichinter` ADD `urgent` INT(2) NOT NULL DEFAULT '0';
ALTER TABLE `llx_fichinter` ADD `type_inter` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `llx_fichinter` ADD `nature_inter` INT(11) NOT NULL DEFAULT '0';