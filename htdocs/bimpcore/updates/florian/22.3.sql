ALTER TABLE `llx_bimpcore_signature` ADD `need_sms_code` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bimpcore_signature` ADD `code_sms_infos` TEXT NOT NULL;