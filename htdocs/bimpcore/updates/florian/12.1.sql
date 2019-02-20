

CREATE TABLE IF NOT EXISTS `llx_bimp_list_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(256) NOT NULL DEFAULT '',
  `owner_type` int(10) UNSIGNED NOT NULL DEFAULT '2',
  `id_owner` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `obj_module` varchar(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `list_type` varchar(128) NOT NULL DEFAULT '',
  `list_name` varchar(128) NOT NULL DEFAULT 'default',
  `panel_name` varchar(128) NOT NULL DEFAULT 'default',
  `filters` text NOT NULL
) ENGINE=InnoDB;