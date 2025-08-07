CREATE TABLE `llx_bds_process_cron_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_cron` int(11) NOT NULL DEFAULT 0,
  `id_option` int(11) NOT NULL DEFAULT 0,
  `value` varchar(255) NOT NULL DEFAULT ''
);



ALTER TABLE `llx_bds_process_cron` ADD INDEX `process` (`id_process`);
ALTER TABLE `llx_bds_process_cron` ADD INDEX `cron` (`id_cronjob`);
ALTER TABLE `llx_bds_process_cron` ADD INDEX `operation` (`id_operation`);

ALTER TABLE `llx_bds_process_cron_option` ADD INDEX `cron` (`id_cron`);
ALTER TABLE `llx_bds_process_cron_option` ADD INDEX `option` (`id_option`);
