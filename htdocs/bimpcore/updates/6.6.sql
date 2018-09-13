

CREATE TABLE IF NOT EXISTS `llx_bimp_propal_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `deletable` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `linked_id_object` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `linked_object_name` varchar(128) NOT NULL DEFAULT '',
  `position` INT UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bs_sav_propal_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `deletable` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `linked_id_object` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `linked_object_name` varchar(128) NOT NULL DEFAULT '',
  `id_reservation` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `out_of_warranty` tinyint(1) NOT NULL DEFAULT '1',
  `position` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

ALTER TABLE `llx_br_reservation` ADD `id_sav_propal_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_sav_product`;