CREATE TABLE IF NOT EXISTS `llx_bimpcore_dictionnary` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `table` varchar(255) NOT NULL,
    `fields` MEDIUMTEXT NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT '1',
	`key_field` varchar(255) NOT NULL,
	`label_field` varchar(255) NOT NULL,
	`active_field` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
);


CREATE TABLE IF NOT EXISTS `llx_bimpcore_dictionnary_value` (
	`dictionnary` varchar(255) NOT NULL DEFAULT '',
	`code` varchar(255) NOT NULL DEFAULT '',
	`label` varchar(255) NOT NULL DEFAULT '',
	`icon` varchar(255) NOT NULL DEFAULT '',
	`class` varchar(255) NOT NULL DEFAULT '',
	`active` tinyint(1) NOT NULL DEFAULT '1',
	`position` int(11) NOT NULL DEFAULT 1
	);
