ALTER TABLE `llx_menu` ADD COLUMN IF NOT EXISTS `synchronised` tinyint(1) NOT NULL DEFAULT 1;
