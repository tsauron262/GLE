ALTER TABLE `llx_bmp_event` ADD `family` INT NOT NULL DEFAULT '0' AFTER `place`;
ALTER TABLE `llx_bmp_event` ADD `style` INT NOT NULL DEFAULT '0' AFTER `place`;
ALTER TABLE `llx_bmp_event` ADD `billets_loc` INT NOT NULL DEFAULT '0';
