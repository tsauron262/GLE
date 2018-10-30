
--UPDATE `llx_extrafields` SET param = 'a:1:{s:7:"options";a:1:{s:28:"entrepot:ref:rowid::statut=1";N;}}' WHERE `name` LIKE 'entrepot';


UPDATE `llx_extrafields` SET list = 1;

UPDATE `llx_cronjob` SET entity = 1 WHERE label LIKE 'Get Reservations GSX';

UPDATE `llx_menu` SET url = '/synopsistools/agenda/vue.php' WHERE url = '/comm/action/index.php';


CREATE TABLE IF NOT EXISTS `llx_bimp_list_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `owner_type` int(10) UNSIGNED NOT NULL DEFAULT '2',
  `id_owner` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `obj_module` varchar(256) NOT NULL DEFAULT '',
  `obj_name` varchar(256) NOT NULL DEFAULT '',
  `list_name` varchar(256) NOT NULL DEFAULT 'default',
  `cols` text NOT NULL,
  `sort_field` varchar(256) NOT NULL DEFAULT 'id',
  `sort_way` varchar(8) NOT NULL DEFAULT 'asc',
  `nb_items` int(11) NOT NULL DEFAULT '10'
) ENGINE=InnoDB;