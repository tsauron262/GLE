CREATE TABLE `llx_bimp_monitoring` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(300) DEFAULT NULL,
  `is_bimperp` boolean DEFAULT 0,
  `user_update` int(11) DEFAULT NULL,
  `user_create` int(11) DEFAULT NULL,
  `date_create` datetime DEFAULT NULL,
  `date_update` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);
