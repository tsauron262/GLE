CREATE TABLE IF NOT EXISTS `llx_bimpcore_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` varchar(30) NOT NULL DEFAULT 'divers',
  `id_user` int(11) NOT NULL DEFAULT 0,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `level` int(11) NOT NULL DEFAULT 1,
  `msg` text NOT NULL,
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `id_object` int(11) NOT NULL DEFAULT 0,
  `extra_data` mediumtext NOT NULL,
  `resolution_infos` text NOT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `id_user_processed` int(11) NOT NULL DEFAULT 0,
  `date_processed` datetime NULL DEFAULT NULL
);