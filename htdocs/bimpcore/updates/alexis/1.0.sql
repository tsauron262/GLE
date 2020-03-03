ALTER TABLE `llx_bnf_period` ADD `status` INT(2) NOT NULL DEFAULT 0;
UPDATE `llx_extrafields` SET `type` = 'sellist', `size` = '', `param` = 'a:1:{s:7:\"options\";a:1:{s:45:\"Synopsis_contrat_annexePdf:modeleName:id::1=1\";N;}}' WHERE `llx_extrafields`.`rowid` = 27;
