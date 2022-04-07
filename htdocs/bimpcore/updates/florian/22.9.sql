CREATE TABLE IF NOT EXISTS `llx_bimpcore_object_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `id_object` int(11) NOT NULL DEFAULT 0,
  `msg` text NOT NULL,
  `code` varchar(255) NOT NULL DEFAULT '',
  `id_user` int(11) NOT NULL DEFAULT 0,
  `date` datetime DEFAULT NULL
);