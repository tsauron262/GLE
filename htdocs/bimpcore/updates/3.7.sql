
ALTER TABLE `llx_be_equipment` DROP `reserved`;

CREATE TABLE IF NOT EXISTS `llx_br_reservation_shipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ref_reservation` varchar(128) NOT NULL DEFAULT '',
  `date` date DEFAULT NULL,
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;