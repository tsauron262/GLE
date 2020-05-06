CREATE TABLE `llx_bds_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `title` varchar(256) NOT NULL,
  `description` text,
  `type` enum('sync','import','export','ws') NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_parameter` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_process` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `label` text NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_process` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `label` text NOT NULL,
  `info` text NOT NULL,
  `type` text NOT NULL,
  `default_value` text NOT NULL,
  `select_values` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_matching_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_process` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `label` varchar(256) NOT NULL,
  `description` text,
  `type` enum('loc_table','custom','','') NOT NULL,
  `ext_label` varchar(128) NOT NULL,
  `loc_label` varchar(128) NOT NULL,
  `loc_table` varchar(128) DEFAULT NULL,
  `field_in` varchar(128) DEFAULT NULL,
  `field_out` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_custom_matching_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_match` int(10) UNSIGNED NOT NULL,
  `loc_value` text NOT NULL,
  `ext_value` text NOT NULL,
  `loc_label` VARCHAR(128) NOT NULL,
  `ext_label` VARCHAR(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_trigger_action` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_process` int(11) NOT NULL,
  `process_name` VARCHAR(128) NOT NULL,
  `action` varchar(128) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_operation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_process` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `title` text NOT NULL,
  `description` text,
  `warning` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `bds_process_operation_option` (
  `id_operation` int(11) NOT NULL,
  `id_option` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_object_sync_data` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `loc_id_process` int(10) UNSIGNED DEFAULT '0',
  `loc_object_name` varchar(128) NOT NULL DEFAULT '',
  `loc_id_object` int(11) NOT NULL DEFAULT '0',
  `ext_id` int(11) NOT NULL DEFAULT '0',
  `ext_id_process` int(11) NOT NULL DEFAULT '0',
  `ext_object_name` varchar(128) NOT NULL DEFAULT '',
  `ext_id_object` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_object_sync_data_object` (
  `id_sync_data` int(10) UNSIGNED NOT NULL,
  `type` varchar(128) NOT NULL,
  `loc_value` text NOT NULL,
  `ext_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_object_import_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_process` int(10) UNSIGNED NOT NULL,
  `object_name` varchar(128) NOT NULL,
  `id_object` int(10) UNSIGNED NOT NULL,
  `import_reference` varchar(128) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `date_add` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_manufacturer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `ref_prefixe` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_cron` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_process` int(10) UNSIGNED NOT NULL,
  `id_operation` int(10) UNSIGNED NOT NULL,
  `id_cronjob` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(256) NOT NULL,
  `description` text,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `frequency_val` INT NOT NULL DEFAULT '1',
  `frequency_type` ENUM('min','hour','day','week') NOT NULL DEFAULT 'min',
  `frequency_start` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_cron_option` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_process_cron` int(10) UNSIGNED NOT NULL,
  `id_option` int(10) UNSIGNED NOT NULL,
  `use_def_val` tinyint(1) NOT NULL DEFAULT '1',
  `value` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;