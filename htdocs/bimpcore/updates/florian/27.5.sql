CREATE TABLE IF NOT EXISTS `llx_bimpcore_note_model` (
	`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`obj_module` varchar(255) NOT NULL DEFAULT '',
	`obj_name` varchar(255) NOT NULL DEFAULT '',
	`name` varchar(255) NOT NULL DEFAULT '',
	`content` MEDIUMTEXT NOT NULL DEFAULT '',
	`active` tinyint(1) NOT NULL DEFAULT 1
	);
