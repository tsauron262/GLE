CREATE TABLE IF NOT EXISTS `llx_concurrence_rdc` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `fk_soc` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `site` varchar(100) NOT NULL DEFAULT '',
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(11) NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `llx_ca_rdc` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_obj` int(11) NOT NULL DEFAULT 0,
  `type_obj` int(11) NOT NULL DEFAULT 0,
  `ca` decimal(24,8) NOT NULL DEFAULT 0,
  `fk_category` int(11) NOT NULL DEFAULT 0,
  `fk_period` int(11) NOT NULL DEFAULT 0,
  `debut_period` date DEFAULT NULL,
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(11) NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);
