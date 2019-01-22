
ALTER TABLE `llx_user` ADD `object_header_locked` BOOLEAN NOT NULL DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS `llx_bmp_vendeur` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_soc` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `label` varchar(256) NOT NULL DEFAULT '',
  `tarifs` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB;