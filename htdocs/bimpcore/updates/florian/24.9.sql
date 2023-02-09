ALTER TABLE `llx_bimpcore_signature` ADD `allow_multiple_files` tinyint (1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bimpcore_signature` ADD `selected_file` VARCHAR (255) NOT NULL DEFAULT '';