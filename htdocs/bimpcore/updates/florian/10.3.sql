
CREATE TABLE IF NOT EXISTS `llx_bimp_commande_fourn_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_line` INT UNSIGNED NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `deletable` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `remisable` tinyint(1) NOT NULL DEFAULT '1',
  `linked_id_object` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `linked_object_name` varchar(256) NOT NULL,
  `remise` decimal(24,8) NOT NULL DEFAULT '0.00000000',
  `position` INT NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `llx_bf_demande_line` ADD `commandes_fourn` TEXT NOT NULL DEFAULT '' AFTER `position`;