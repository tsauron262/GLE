
--UPDATE `llx_extrafields` SET param = 'a:1:{s:7:"options";a:1:{s:28:"entrepot:ref:rowid::statut=1";N;}}' WHERE `name` LIKE 'entrepot';


UPDATE `llx_extrafields` SET list = 1;

UPDATE `llx_cronjob` SET entity = 1 WHERE label LIKE 'Get Reservations GSX';

UPDATE `llx_menu` SET url = '/synopsistools/agenda/vue.php' WHERE url = '/comm/action/index.php';
