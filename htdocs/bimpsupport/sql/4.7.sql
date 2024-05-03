ALTER TABLE `llx_bs_apple_part` ADD `warranty` INT NOT NULL DEFAULT 0;
UPDATE llx_bs_apple_part SET warranty = 1 WHERE out_of_warranty = 0;

ALTER TABLE `llx_bs_sav_propal_line` ADD `warranty` INT NOT NULL DEFAULT 0;
UPDATE llx_bs_sav_propal_line SET warranty = 1 WHERE out_of_warranty = 0;