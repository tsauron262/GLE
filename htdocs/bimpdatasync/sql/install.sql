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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_object_sync_data_object` (
  `id_sync_data` int(10) UNSIGNED NOT NULL,
  `type` varchar(128) NOT NULL,
  `loc_value` text NOT NULL,
  `ext_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bds_process_trigger_action` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_process` int(11) NOT NULL,
  `process_name` VARCHAR(128) NOT NULL,
  `action` varchar(128) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;