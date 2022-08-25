CREATE TABLE IF NOT EXISTS `llx_bws_profile` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `user_create` int(11) NOT NULL DEFAULT 0,
  `date_create` datetime DEFAULT NULL,
  `user_update` int(11) NOT NULL DEFAULT 0,
  `date_update` datetime DEFAULT NULL,
  UNIQUE KEY `name` (`name`)
);

CREATE TABLE `llx_bws_profile_right` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_profile` int(11) NOT NULL DEFAULT 0,
  `request_name` varchar(255) NOT NULL DEFAULT '',
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `obj_filters` text NOT NULL,
  KEY `id_profile` (`id_profile`)
);

