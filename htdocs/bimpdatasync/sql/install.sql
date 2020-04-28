CREATE TABLE `llx_bds_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL DEFAULT '',
  `type` varchar(30) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_create` int(11) NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(11) NOT NULL DEFAULT 0
);

CREATE TABLE `llx_bds_process_operation` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_process` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL DEFAULT '',
  `warning` text NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `use_report` tinyint(1) NOT NULL DEFAULT 1
);

CREATE TABLE `llx_bds_process_param` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_process` int(11) NOT NULL DEFAULT 0,
  `label` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE `llx_bds_process_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_process` int(11) NOT NULL DEFAULT 0,
  `label` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` text NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL,
  `select_values` text NOT NULL,
  `default_value` varchar(255) NOT NULL DEFAULT '',
  `required` tinyint(1) NOT NULL DEFAULT 0,
);

CREATE TABLE `llx_bds_process_trigger` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_process` int(11) NOT NULL DEFAULT 0,
  `action_name` varchar(255) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT 1
);

CREATE TABLE `llx_bds_process_match` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_process` int(11) NOT NULL DEFAULT 0,
  `label` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL,
  `ext_label` varchar(255) NOT NULL DEFAULT '',
  `loc_label` varchar(255) NOT NULL DEFAULT '',
  `loc_table` varchar(255) NOT NULL DEFAULT '',
  `field_in` varchar(255) NOT NULL DEFAULT '',
  `field_out` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE `llx_bds_process_match_custom_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_match` int(11) NOT NULL DEFAULT 0,
  `loc_label` varchar(255) NOT NULL DEFAULT '',
  `loc_value` varchar(255) NOT NULL DEFAULT '',
  `ext_label` varchar(255) NOT NULL DEFAULT '',
  `ext_value` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE `llx_bds_process_cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_process` int(11) NOT NULL DEFAULT 0,
  `id_cronjob` int(11) NOT NULL DEFAULT 0,
  `id_operation` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `freq_val` int(11) NOT NULL DEFAULT 1,
  `freq_type` varchar(10) NOT NULL DEFAULT '',
  `start` datetime NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE `llx_bds_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_process` int(11) NOT NULL DEFAULT 0,
  `id_operation` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(30) NOT NULL,
  `begin` datetime NOT NULL DEFAULT current_timestamp(),
  `end` datetime DEFAULT NULL,
  `nb_successes` int(11) NOT NULL DEFAULT 0,
  `nb_errors` int(11) NOT NULL DEFAULT 0,
  `nb_warnings` int(11) NOT NULL DEFAULT 0,
  `nb_infos` int(11) NOT NULL DEFAULT 0
);

CREATE TABLE `llx_bds_report_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_report` int(11) NOT NULL DEFAULT 0,
  `obj_module` varchar(30) NOT NULL DEFAULT '',
  `obj_name` varchar(30) NOT NULL DEFAULT '',
  `id_obj` int(11) NOT NULL DEFAULT 0,
  `ref` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(30) NOT NULL,
  `time` time DEFAULT NULL,
  `msg` text NOT NULL DEFAULT ''
);

CREATE TABLE `llx_bds_report_object_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_report` int(11) NOT NULL DEFAULT 0,
  `obj_module` varchar(30) NOT NULL DEFAULT '',
  `obj_name` varchar(30) NOT NULL DEFAULT '',
  `nbProcessed` int(11) NOT NULL DEFAULT 0,
  `nbUpdated` int(11) NOT NULL DEFAULT 0,
  `nbCreated` int(11) NOT NULL DEFAULT 0,
  `nbDeleted` int(11) NOT NULL DEFAULT 0,
  `nbIgnored` int(11) NOT NULL DEFAULT 0,
  `nbActivated` int(11) NOT NULL DEFAULT 0,
  `nbDeactivated` int(11) NOT NULL DEFAULT 0
);