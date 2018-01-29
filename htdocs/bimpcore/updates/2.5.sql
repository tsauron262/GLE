ALTER TABLE `llx_bh_equipment` RENAME TO llx_be_equipment;

ALTER TABLE `llx_be_equipment` ADD `type` INT NOT NULL DEFAULT '0' AFTER `id_product`;
ALTER TABLE `llx_be_equipment` ADD `reserved` BOOLEAN NOT NULL DEFAULT FALSE AFTER `warranty_type`;

CREATE TABLE IF NOT EXISTS `llx_be_equipment_place` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_equipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '1',
  `id_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_contact` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `place_name` varchar(256) NOT NULL DEFAULT '',
  `infos` text NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `position` int(11) NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;