UPDATE `llx_bimpcore_note` SET `visibility` = 10 WHERE `visibility` = 3;
UPDATE `llx_bimpcore_note` SET `visibility` = 20 WHERE `visibility` = 4;

ALTER TABLE `llx_bimpcore_note` ADD `delete_on_view` tinyint(1) NOT NULL DEFAULT 0;