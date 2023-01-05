CREATE TABLE IF NOT EXISTS `llx_bds_sync_object` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_module` varchar(255) NOT NULL DEFAULT '',
  `obj_name` varchar(255) NOT NULL DEFAULT '',
  `id_loc` int(11) NOT NULL DEFAULT 0,
  `id_ext` int(11) NOT NULL DEFAULT 0,
  `sync_date` datetime NULL DEFAULT NULL,
  `id_process` int(11) NOT NULL DEFAULT 0
);

ALTER TABLE `llx_bds_sync_object` ADD INDEX(`id_process`); 
ALTER TABLE `llx_bds_sync_object` ADD INDEX( `obj_module`, `obj_name`, `id_loc`);
ALTER TABLE `llx_bds_sync_object` ADD INDEX( `obj_module`, `obj_name`, `id_ext`);