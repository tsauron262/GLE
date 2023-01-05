CREATE TABLE IF NOT EXISTS `llx_bimpcore_object_lock` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_module` varchar(30) DEFAULT '',
  `obj_name` varchar(30) DEFAULT '',
  `id_object` int(11) DEFAULT 0,
  `tms` int(11) DEFAULT 0,
  `id_user` int(11) DEFAULT 0
);