CREATE TABLE IF NOT EXISTS `llx_bimpcore_list_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `owner_type` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `id_owner` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `obj_module` varchar(256) NOT NULL DEFAULT '',
  `obj_name` varchar(256) NOT NULL DEFAULT '',
  `list_name` varchar(256) NOT NULL DEFAULT 'default',
  `cols` text NOT NULL,
  `sort_field` varchar(256) NOT NULL DEFAULT 'id',
  `sort_way` varchar(8) NOT NULL DEFAULT 'asc',
  `nb_items` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB;

ALTER TABLE `llx_bimpcore_list_config` ADD `sort_option` VARCHAR(256) NOT NULL DEFAULT '' AFTER `sort_way`; 