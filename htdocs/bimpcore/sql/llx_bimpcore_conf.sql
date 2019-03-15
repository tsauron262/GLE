
-- Pour la retrocompatibilité
ALTER TABLE llx_bimp_file RENAME TO llx_bimpcore_file;
ALTER TABLE llx_bimp_history RENAME TO llx_bimpcore_history;
ALTER TABLE llx_bimp_list_config RENAME TO llx_bimpcore_list_config;
ALTER TABLE llx_bimp_list_filters RENAME TO llx_bimpcore_list_filters;
ALTER TABLE llx_bimp_note RENAME TO llx_bimpcore_note;
ALTER TABLE llx_bimp_objects_associations RENAME TO llx_bimpcore_objects_associations;
ALTER TABLE llx_bimp_timer RENAME TO llx_bimpcore_timer;
ALTER TABLE llx_bimp_note RENAME TO llx_bimpcore_note;





CREATE TABLE IF NOT EXISTS `llx_bimpcore_conf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- Affichage de la table llx_bimpcore_file
-- ------------------------------------------------------------

-- CREATE TABLE IF NOT EXISTS `llx_bimpcore_file` (
--   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `parent_module` varchar(128) DEFAULT NULL,
--   `parent_object_name` varchar(128) NOT NULL,
--   `id_parent` int(10) unsigned NOT NULL DEFAULT 0,
--   `file_name` varchar(128) NOT NULL,
--   `file_ext` varchar(12) DEFAULT NULL,
--   `file_size` int(11) NOT NULL,
--   `description` text NOT NULL,
--   `user_create` int(10) unsigned NOT NULL DEFAULT 0,
--   `date_create` datetime NOT NULL DEFAULT current_timestamp(),
--   `user_update` int(10) unsigned NOT NULL DEFAULT 0,
--   `date_update` datetime NOT NULL DEFAULT current_timestamp(),
--   `deleted` tinyint(1) NOT NULL DEFAULT 0,
--   `date_delete` datetime DEFAULT NULL,
--   `user_delete` int(10) unsigned NOT NULL DEFAULT 0,
--   PRIMARY KEY (`id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `llx_bimpcore_file` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_module` varchar(128) DEFAULT NULL,
  `parent_object_name` varchar(128) NOT NULL,
  `id_parent` int(10) unsigned NOT NULL DEFAULT 0,
  `file_name` varchar(128) NOT NULL,
  `file_ext` varchar(12) DEFAULT NULL,
  `file_size` int(11) NOT NULL,
  `description` text NOT NULL,
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_create` datetime,
  `user_update` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `date_delete` datetime DEFAULT NULL,
  `user_delete` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



-- Affichage de la table llx_bimpcore_history
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `llx_bimpcore_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(128) NOT NULL,
  `object` varchar(128) NOT NULL,
  `id_object` int(10) unsigned NOT NULL,
  `field` varchar(128) NOT NULL,
  `value` text NOT NULL,
  `date` datetime,
  `id_user` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



-- Affichage de la table llx_bimpcore_list_config
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `llx_bimpcore_list_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_type` int(10) unsigned NOT NULL DEFAULT 2,
  `id_owner` int(10) unsigned NOT NULL DEFAULT 0,
  `obj_module` varchar(256) NOT NULL DEFAULT '',
  `obj_name` varchar(256) NOT NULL DEFAULT '',
  `list_name` varchar(256) NOT NULL DEFAULT 'default',
  `cols` text NOT NULL,
  `sort_field` varchar(256) NOT NULL DEFAULT 'id',
  `sort_option` varchar(128) NOT NULL DEFAULT '',
  `sort_way` varchar(8) NOT NULL DEFAULT 'asc',
  `nb_items` int(11) NOT NULL DEFAULT 10,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



-- Affichage de la table llx_bimpcore_list_filters
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `llx_bimpcore_list_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL DEFAULT '',
  `owner_type` int(10) unsigned NOT NULL DEFAULT 2,
  `id_owner` int(10) unsigned NOT NULL DEFAULT 0,
  `obj_module` varchar(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `list_type` varchar(128) NOT NULL DEFAULT '',
  `list_name` varchar(128) NOT NULL DEFAULT 'default',
  `panel_name` varchar(128) NOT NULL DEFAULT 'default',
  `filters` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



-- Affichage de la table llx_bimpcore_note
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `llx_bimpcore_note` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `obj_type` varchar(128) NOT NULL DEFAULT '',
  `obj_module` varchar(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `id_obj` int(10) unsigned NOT NULL DEFAULT 0,
  `type_author` int(11) NOT NULL DEFAULT 1,
  `id_societe` int(11) NOT NULL DEFAULT 0,
  `email` varchar(256) NOT NULL DEFAULT '',
  `viewed` int(11) NOT NULL DEFAULT 1,
  `visibility` int(10) unsigned NOT NULL DEFAULT 2,
  `content` text NOT NULL,
  `date_create` datetime ,
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime,
  `user_update` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



-- Affichage de la table llx_bimpcore_objects_associations
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `llx_bimpcore_objects_associations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `association` varchar(128) NOT NULL DEFAULT '',
  `src_object_module` varchar(128) NOT NULL,
  `src_object_name` varchar(128) NOT NULL,
  `src_object_type` varchar(128) NOT NULL,
  `src_id_object` int(10) unsigned NOT NULL,
  `dest_object_module` varchar(128) NOT NULL,
  `dest_object_name` varchar(128) NOT NULL,
  `dest_object_type` varchar(128) NOT NULL,
  `dest_id_object` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



-- Affichage de la table llx_bimpcore_timer
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `llx_bimpcore_timer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `obj_module` varchar(128) NOT NULL,
  `obj_name` varchar(128) NOT NULL,
  `id_obj` int(10) unsigned NOT NULL,
  `field_name` varchar(128) NOT NULL,
  `time_session` int(11) NOT NULL,
  `session_start` int(11) DEFAULT NULL,
  `id_user` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;





