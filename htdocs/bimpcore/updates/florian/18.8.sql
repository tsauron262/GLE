ALTER TABLE `llx_bimpcore_list_config` ADD `name` VARCHAR (255) NOT NULL DEFAULT '' AFTER `id`;
ALTER TABLE `llx_bimpcore_list_config` ADD `list_type` VARCHAR(255) NOT NULL DEFAULT '' AFTER `obj_name`; 
ALTER TABLE `llx_bimpcore_list_config` ADD `id_default_filters` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimpcore_list_config` ADD `search_open` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_bimpcore_list_config` ADD `filters_open` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_bimpcore_list_config` ADD `is_default` BOOLEAN NOT NULL DEFAULT FALSE; 

UPDATE `llx_bimpcore_list_config` SET `list_type` = 'list_table' WHERE `list_type` = '';
UPDATE `llx_bimpcore_list_config` SET `name` = 'Configuration utilisateur par défaut' WHERE `name` = '';

CREATE TABLE IF NOT EXISTS `llx_bimpcore_list_current_config` (
  `id_user` int(11) NOT NULL DEFAULT 0,
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `list_type` varchar(255) NOT NULL DEFAULT '',
  `list_name` varchar(255) NOT NULL DEFAULT '',
  `id_config` int(11) NOT NULL DEFAULT 0
);

ALTER TABLE `llx_bimpcore_list_current_config`
  ADD UNIQUE KEY `user_list_config` (`id_user`,`obj_module`,`obj_name`,`list_type`,`list_name`,`id_config`);