
CREATE TABLE IF NOT EXISTS `llx_bimp_facture_fourn_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `deletable` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `remisable` tinyint(1) NOT NULL DEFAULT '1',
  `linked_id_object` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `linked_object_name` varchar(128) NOT NULL DEFAULT '',
  `remise` double(24,8) NOT NULL DEFAULT '0.00000000',
  `position` INT NOT NULL DEFAULT '0'
) ENGINE=InnoDB;