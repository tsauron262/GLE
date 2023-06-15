ALTER TABLE `llx_bimp_task` ADD `id_task` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bimp_task` ADD `auto` int(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bimp_task` ADD `type_manuel` VARCHAR(30) NOT NULL DEFAULT '';