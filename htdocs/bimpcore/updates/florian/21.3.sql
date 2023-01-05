ALTER TABLE `llx_bimpcore_log` ADD `url` VARCHAR(255) NOT NULL DEFAULT '' AFTER `msg`;
ALTER TABLE `llx_bimpcore_log` ADD `url_params` TEXT NOT NULL DEFAULT '' AFTER `url`;