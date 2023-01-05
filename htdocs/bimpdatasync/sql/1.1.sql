CREATE TABLE `llx_bds_process_cron_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_cron` int(11) NOT NULL DEFAULT 0,
  `id_option` int(11) NOT NULL DEFAULT 0,
  `value` varchar(255) NOT NULL DEFAULT ''
);