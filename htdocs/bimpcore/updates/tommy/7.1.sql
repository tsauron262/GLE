ALTER TABLE `llx_bimpcore_note` ADD `fk_group_author` int NOT NULL DEFAULT 0;

UPDATE `llx_bimpcore_note` SET `type_dest` = 4 WHERE `type_dest` = 2;
