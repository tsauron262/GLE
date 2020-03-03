CREATE TABLE `llx_bic_user_contrat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_contrat` int(11) NOT NULL,
  `read_ticket_in_contrat` int(11) NOT NULL COMMENT '1 => Lire tous, 0 => Lire que les siens (TICKETS)',
  `date_create` datetime DEFAULT NULL,
  `user_create` int(11) DEFAULT NULL,
  `date_update` datetime DEFAULT NULL,
  `user_update` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
