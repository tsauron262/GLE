ALTER TABLE `llx_bs_sav` ADD `code_centre_repa` VARCHAR(16) NOT NULL DEFAULT '' AFTER `code_centre`;
UPDATE `llx_bs_sav` SET `code_centre_repa` = `code_centre` WHERE `code_centre_repa` = '';