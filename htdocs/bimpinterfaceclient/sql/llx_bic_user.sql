CREATE TABLE IF NOT EXISTS `llx_bic_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT 'Crypter en SHA256',
  `attached_contrat` text COMMENT 'JSON ARRAY',
  `attached_societe` int(11) NOT NULL COMMENT 'socid',
  `role` int(11) NOT NULL COMMENT '1 = Admin, 0 = User',
  `date_create` datetime NOT NULL,
  `user_create` int(11) NOT NULL,
  `date_update` datetime DEFAULT NULL,
  `user_update` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `renew_required` int(5) NOT NULL DEFAULT '1',
  `lang` varchar(5) NOT NULL DEFAULT 'fr_fr',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;