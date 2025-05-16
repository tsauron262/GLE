CREATE TABLE IF NOT EXISTS llx_bimp_user_share (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`obj_module` varchar(100) NOT NULL DEFAULT '',
	`obj_name` varchar(100) NOT NULL DEFAULT '',
	`id_obj` int(11) NOT NULL DEFAULT 0,
	`id_user` int(11) NOT NULL DEFAULT 0,
	`can_edit` tinyint(1) NOT NULL DEFAULT 0,
);

CREATE TABLE IF NOT EXISTS llx_bimp_usergroup_share (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`obj_module` varchar(100) NOT NULL DEFAULT '',
	`obj_name` varchar(100) NOT NULL DEFAULT '',
	`id_obj` int(11) NOT NULL DEFAULT 0,
	`id_group` int(11) NOT NULL DEFAULT 0,
	`can_edit` tinyint(1) NOT NULL DEFAULT 0,
);
