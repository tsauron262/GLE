ALTER TABLE `llx_be_logiciel` DROP `id_user_account`, DROP `sn`;

CREATE TABLE IF NOT EXISTS `llx_be_equipment_logiciel` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_equipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_logiciel` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_user_account` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `serial` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;