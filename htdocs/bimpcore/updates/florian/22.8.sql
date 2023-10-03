ALTER TABLE `llx_societe` CHANGE `date_last_active` `date_last_activity` DATE default NULL;
ALTER TABLE `llx_societe` ADD `last_activity_origin` TEXT NOT NULL;

CREATE TABLE IF NOT EXISTS `llx_societe_saved_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_client` int(11) NOT NULL DEFAULT 0,
  `data` mediumtext NOT NULL,
  `date` date DEFAULT NULL
);