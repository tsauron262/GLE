

CREATE TABLE IF NOT EXISTS `llx_bimp_facture_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `deletable` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `linked_id_object` int(11) NOT NULL DEFAULT '0',
  `linked_object_name` varchar(255) NOT NULL DEFAULT '',
  `position` INT NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_object_line_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_object_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `object_type` VARCHAR(128) NOT NULL DEFAULT '',
  `id_equipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `pu_ht` float NOT NULL DEFAULT '0',
  `tva_tx` float NOT NULL DEFAULT '0',
  `pa_ht` float NOT NULL DEFAULT '0',
  `id_fourn_price` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `llx_be_equipment` ADD `available` BOOLEAN NOT NULL DEFAULT TRUE AFTER `serial`;

CREATE TABLE IF NOT EXISTS `llx_bimp_commande_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `deletable` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `linked_id_object` int(11) NOT NULL DEFAULT '0',
  `linked_object_name` varchar(255) NOT NULL DEFAULT '',
  `position` INT NOT NULL DEFAULT '0',
  `qty_shipped` INT NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

