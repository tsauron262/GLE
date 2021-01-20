CREATE TABLE IF NOT EXISTS `llx_buc_user_current_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `config_object_name` varchar(255) NOT NULL DEFAULT '',
  `config_type_key` varchar(500) NOT NULL DEFAULT '',
  `id_user` int(11) NOT NULL DEFAULT 0,
  `id_config` int(11) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS `llx_buc_list_table_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL DEFAULT '',
  `owner_type` int(11) NOT NULL DEFAULT 2,
  `id_owner` int(11) NOT NULL DEFAULT 0,
  `shared_users` text NOT NULL DEFAULT '',
  `shared_groups` text NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `id_user_create` int(11) NOT NULL DEFAULT 0,
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `component_name` varchar(255) NOT NULL DEFAULT '',
  `sort_field` varchar(255) NOT NULL DEFAULT '',
  `sort_option` varchar(255) NOT NULL DEFAULT '',
  `sort_way` varchar(4) DEFAULT 'asc',
  `nb_items` int(11) NOT NULL DEFAULT 10,
  `cols` mediumtext NOT NULL DEFAULT '',
  `total_row` tinyint(1) NOT NULL DEFAULT 0,
  `id_default_filters_config` int(11) NOT NULL DEFAULT 0,
  `id_default_filters` int(11) NOT NULL DEFAULT 0,
  `active_filters` tinyint(1) NOT NULL DEFAULT 0,
  `search_open` tinyint(1) NOT NULL DEFAULT 0,
  `filters_open` tinyint(1) NOT NULL DEFAULT 0,
  `sheet_name` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `llx_buc_stats_list_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL DEFAULT '',
  `owner_type` int(11) NOT NULL DEFAULT 2,
  `id_owner` int(11) NOT NULL DEFAULT 0,
  `shared_users` text NOT NULL DEFAULT '',
  `shared_groups` text NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `id_user_create` int(11) NOT NULL DEFAULT 0,
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `component_name` varchar(255) NOT NULL DEFAULT '',
  `sort_field` varchar(255) NOT NULL DEFAULT '',
  `sort_option` varchar(255) NOT NULL DEFAULT '',
  `sort_way` varchar(4) DEFAULT 'asc',
  `nb_items` int(11) NOT NULL DEFAULT 10,
  `cols` mediumtext NOT NULL DEFAULT '',
  `total_row` tinyint(1) NOT NULL DEFAULT 0,
  `id_default_filters_config` int(11) NOT NULL DEFAULT 0,
  `id_default_filters` int(11) NOT NULL DEFAULT 0,
  `active_filters` tinyint(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS `llx_buc_list_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL DEFAULT '',
  `owner_type` int(11) NOT NULL DEFAULT 2,
  `id_owner` int(11) NOT NULL DEFAULT 0,
  `shared_users` text NOT NULL DEFAULT '',
  `shared_groups` text NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `id_user_create` int(11) NOT NULL DEFAULT 0,
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `filters` mediumtext NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `llx_buc_filters_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL DEFAULT '',
  `owner_type` int(11) NOT NULL DEFAULT 2,
  `id_owner` int(11) NOT NULL DEFAULT 0,
  `shared_users` text NOT NULL DEFAULT '',
  `shared_groups` text NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `id_user_create` int(11) NOT NULL DEFAULT 0,
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `filters` mediumtext NOT NULL DEFAULT ''
);