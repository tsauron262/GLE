ALTER TABLE `llx_bs_sav` ADD `status_ecologic` INT NOT NULL DEFAULT 0;
UPDATE llx_bs_sav SET status_ecologic = -1 WHERE status IN (999, -3,-2,-4) AND status_ecologic = 0;
ALTER TABLE `llx_bs_sav` ADD `ecologic_data` TEXT NOT NULL DEFAULT '';

