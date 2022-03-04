CREATE TABLE IF NOT EXISTS `llx_bimpmail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `obj_type` varchar(128) NOT NULL DEFAULT '',
  `obj_module` varchar(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `id_obj` int(10) unsigned NOT NULL DEFAULT 0,
  `mail_to` varchar(128) NOT NULL DEFAULT '',
  `mail_from` varchar(128) NOT NULL DEFAULT '',
  `mail_cc` varchar(128) NOT NULL DEFAULT '',
  `mail_subject` varchar(128) NOT NULL DEFAULT '',
  `old_trace` varchar(128) NOT NULL DEFAULT '',
  `msg` text NOT NULL DEFAULT '',
  `msg_id` varchar(128) NOT NULL DEFAULT '',
  `date_create` datetime ,
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime,
  `user_update` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
