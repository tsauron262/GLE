CREATE TABLE `llx_bimp_coop_nonrep` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `value` float(20,4) DEFAULT NULL,
  `info` varchar(300) DEFAULT NULL,
  `user_update` int(11) DEFAULT NULL,
  `user_create` int(11) DEFAULT NULL,
  `date_create` datetime DEFAULT NULL,
  `date_update` datetime DEFAULT NULL,
  `categorie` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
