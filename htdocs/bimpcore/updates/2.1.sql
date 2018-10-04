ALTER TABLE `llx_bh_ticket` ADD `symptomes` TEXT NOT NULL DEFAULT '' AFTER `impact`;
ALTER TABLE `llx_bh_ticket` ADD `important` BOOLEAN NOT NULL DEFAULT TRUE AFTER `impact`;
ALTER TABLE `llx_bh_ticket` ADD `status` INT UNSIGNED NOT NULL DEFAULT '1' AFTER `impact`;
ALTER TABLE `llx_bh_ticket` ADD `cover` INT UNSIGNED NOT NULL DEFAULT '1' AFTER `impact`;
ALTER TABLE `llx_bh_ticket` ADD `appels_timer` INT NOT NULL DEFAULT '0' AFTER `impact`;

