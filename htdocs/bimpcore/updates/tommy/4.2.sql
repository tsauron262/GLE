CREATE TABLE `llx_bimpclient_suivi_recouv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_societe` int(11) NOT NULL DEFAULT 0,
  `sens` int(11) NOT NULL DEFAULT 0,
  `mode` int(11) NOT NULL DEFAULT 0,
  `content` text NOT NULL,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_societe` (`id_societe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;