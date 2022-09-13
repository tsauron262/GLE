ALTER TABLE `llx_bimpcore_alert` ADD `type` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bimpcore_alert` MODIFY `msg` text NOT NULL;