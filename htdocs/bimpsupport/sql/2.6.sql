ALTER TABLE `llx_bs_inter` ADD `is_public` BOOLEAN NOT NULL DEFAULT 0 AFTER `resolution`;
ALTER TABLE `llx_bs_sav` ADD `id_user_client` INT(11) NOT NULL DEFAULT 0 AFTER `id_contact`;