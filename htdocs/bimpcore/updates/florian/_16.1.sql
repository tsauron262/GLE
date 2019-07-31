
INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('default_comm_rate', 7);

CREATE TABLE IF NOT EXISTS `bimp_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `total_marges` decimal(24,8) NOT NULL,
  `rate` decimal(12,4) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `applied` tinyint(1) NOT NULL DEFAULT 0
);