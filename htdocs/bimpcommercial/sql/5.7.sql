CREATE TABLE IF NOT EXISTS `llx_object_line_remise_arriere` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_object_line` int(11) NOT NULL DEFAULT 0,
  `object_type` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(30) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  `value` DECIMAL(24,6) NOT NULL DEFAULT 0,
  KEY `id_object_line` (`id_object_line`,`object_type`)
)
