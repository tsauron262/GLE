
CREATE TABLE IF NOT EXISTS `llx_bimp_gsx_repair` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `serial` varchar(128) NOT NULL DEFAULT '',
  `id_sav` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `repair_number` varchar(128) NOT NULL DEFAULT '',
  `repair_confirm_number` varchar(128) NOT NULL DEFAULT '',
  `serial_update_confirm_number` varchar(128) NOT NULL DEFAULT '',
  `repair_type` varchar(128) NOT NULL DEFAULT '',
  `total_from_order` float DEFAULT NULL,
  `ready_for_pick_up` tinyint(1) NOT NULL DEFAULT '0',
  `reimbursed` tinyint(1) NOT NULL DEFAULT '0',
  `repair_complete` tinyint(1) NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `date_closed` date DEFAULT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE `llx_bs_apple_part` ADD `id_sav` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;

ALTER TABLE `llx_bs_sav` ADD `code_centre` VARCHAR(128) NOT NULL DEFAULT '' AFTER `status`;