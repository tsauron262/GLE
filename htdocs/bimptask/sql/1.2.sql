ALTER TABLE `llx_bimp_task` CHANGE `sous_type` `sous_type` VARCHAR (30) NOT NULL DEFAULT '';
UPDATE `llx_bimp_task` SET `sous_type` = 'bug' WHERE `sous_type` = '0';
UPDATE `llx_bimp_task` SET `sous_type` = 'dev' WHERE `sous_type` = '1';