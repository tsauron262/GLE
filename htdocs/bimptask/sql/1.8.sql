ALTER TABLE `llx_bimp_task` ADD `ok_metier` tinyint(1) NOT NULL DEFAULT 0;
UPDATE llx_bimp_task SET ok_metier = 1 WHERE status  = 4;