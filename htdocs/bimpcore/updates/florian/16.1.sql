
INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('default_comm_rate', 7);

CREATE TABLE IF NOT EXISTS `llx_bimp_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date` date DEFAULT NULL,
  `total_marges` decimal(24,8) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS `llx_bimp_revalorisation` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_facture_line` int(11) NOT NULL DEFAULT 0,
  `id_user` int(11) NOT NULL DEFAULT 0,
  `type` varchar(255) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp,
  `amount` decimal(24,8) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `applied` BOOLEAN NOT NULL DEFAULT FALSE
);