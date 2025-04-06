CREATE TABLE IF NOT EXISTS `llx_bimpcore_dictionnary` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `code` varchar(255) NOT NULL DEFAULT '',
    `name` varchar(255) NOT NULL DEFAULT '',
	`values_params` MEDIUMTEXT NOT NULL DEFAULT '',
    `active` tinyint(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `code` (`code`)
);

CREATE TABLE IF NOT EXISTS `llx_bimpcore_dictionnary_value` (
	`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`id_dict` int(11) NOT NULL DEFAULT 0,
	`code` varchar(255) NOT NULL DEFAULT '',
	`label` varchar(255) NOT NULL DEFAULT '',
	`icon` varchar(255) NOT NULL DEFAULT '',
	`class` varchar(255) NOT NULL DEFAULT '',
	`active` tinyint(1) NOT NULL DEFAULT 1,
	`position` int(11) NOT NULL DEFAULT 1,
	`extra_data` TEXT NOT NULL DEFAULT '',
	UNIQUE KEY `code` (`id_dict`, `code`)
);
