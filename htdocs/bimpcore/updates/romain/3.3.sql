ALTER TABLE `llx_societe_extrafields` CHANGE `validation_financiere` `validation_financiere` INT(1) NULL DEFAULT '1';
UPDATE `llx_societe_extrafields` SET `validation_financiere` = 1