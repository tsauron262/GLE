CREATE TABLE IF NOT EXISTS `llx_concurrence_rdc` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `fk_soc` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `site` varchar(100) NOT NULL DEFAULT '',
  `user_update` int(11) NOT NULL,
  `date_update` datetime NOT NULL ,
  `user_create` int(11) NOT NULL,
  `date_create` datetime NOT NULL
);

CREATE TABLE IF NOT EXISTS `llx_ca_rdc` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `fk_soc` int(11) NOT NULL,
  `fk_category` int(11) NOT NULL,
  `ca_ytd` decimal(24,8) NOT NULL,
  `ca_ytd_n1` decimal(24,8) NOT NULL,
  `evol_ytd_n1` decimal(24,8) NOT NULL,
  `ca_n1` decimal(24,8) NOT NULL,
  `ca_s1` decimal(24,8) NOT NULL,
  `ca_m1` decimal(24,8) NOT NULL,
  `ca_m2` decimal(24,8) NOT NULL,
  `evol_m1_m2` decimal(24,8) NOT NULL,
  `ca_total` decimal(24,8) NOT NULL,
  `user_update` int(11) NOT NULL,
  `date_update` datetime NOT NULL ,
  `user_create` int(11) NOT NULL,
  `date_create` datetime NOT NULL
);
