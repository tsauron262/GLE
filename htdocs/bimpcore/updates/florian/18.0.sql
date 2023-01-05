INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('use_gsx_v2', 0);

CREATE TABLE IF NOT EXISTS `llx_bs_sav_issue` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_sav` int(11) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL DEFAULT '',
  `category_code` varchar(7) NOT NULL DEFAULT '',
  `category_label` varchar(255) NOT NULL DEFAULT '',
  `issue_code` varchar(7) NOT NULL DEFAULT '',
  `issue_label` varchar(255) NOT NULL DEFAULT '',
  `reproducibility` varchar(1) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT 0
);

ALTER TABLE `llx_bs_apple_part` ADD `id_issue` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bs_apple_part` ADD `is_tier` int(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bs_apple_part` ADD `not_invoiced` int(1) NOT NULL DEFAULT 0;

ALTER TABLE `llx_bimp_gsx_repair` ADD `old_repair_number` VARCHAR(128) NOT NULL DEFAULT '' AFTER `canceled`; 

-- UPDATE llx_bimp_gsx_repair SET `old_repair_number` = `repair_number`;
-- UPDATE llx_bimp_gsx_repair SET `repair_number` = `repair_confirm_number`;