CREATE TABLE IF NOT EXISTS `llx_bimp_remise_globale` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_type` varchar(24) NOT NULL DEFAULT '',
  `id_obj` int(11) NOT NULL DEFAULT 0,
  `label` text NOT NULL DEFAULT '',
  `type` varchar(24) NOT NULL DEFAULT 'amount',
  `amount` decimal(24,2) NOT NULL DEFAULT 0.00,
  `percent` decimal(24,4) NOT NULL DEFAULT 0.0000,
  `user_create` int(11) NOT NULL DEFAULT 0,
  `date_create` datetime NOT NULL DEFAULT current_timestamp,
  `user_update` int(11) NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp
);

ALTER TABLE `llx_bimp_remise_globale` ADD INDEX( `obj_type`, `id_obj`);
ALTER TABLE `llx_object_line_remise` ADD `id_remise_globale` INT NOT NULL DEFAULT '0' AFTER `is_remise_globale`; 
ALTER TABLE `llx_object_line_remise` ADD `linked_id_remise_globale` INT NOT NULL DEFAULT '0' AFTER `id_remise_globale`;