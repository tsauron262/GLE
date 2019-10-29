INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('use_gsx_v2', 0);

-- ALTER TABLE `llx_bs_apple_part` ADD `issue_category` VARCHAR(8) NOT NULL DEFAULT '';
-- ALTER TABLE `llx_bs_apple_part` ADD `issue_code` VARCHAR(8) NOT NULL DEFAULT '';
-- ALTER TABLE `llx_bs_apple_part` ADD `reproducibility` VARCHAR(2) NOT NULL DEFAULT '';
-- ALTER TABLE `llx_bs_apple_part` ADD `issue_label` VARCHAR(255) NOT NULL DEFAULT '';


CREATE TABLE IF NOT EXISTS `llx_bs_sav_issue` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_sav` int(11) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL DEFAULT '',
  `category_code` varchar(7) NOT NULL DEFAULT '',
  `category_label` varchar(255) NOT NULL DEFAULT '',
  `issue_code` varchar(7) NOT NULL DEFAULT '',
  `issue_label` varchar(255) NOT NULL DEFAULT '',
  `reproducibility` varchar(1) NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0
);

ALTER TABLE `llx_bs_apple_part` ADD `id_issue` int(11) NOT NULL DEFAULT 0;