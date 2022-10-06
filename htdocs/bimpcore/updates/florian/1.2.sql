CREATE TABLE IF NOT EXISTS `llx_bh_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_product` int(10) UNSIGNED NOT NULL,
  `serial` varchar(128) NOT NULL,
  `date_purchase` date DEFAULT NULL,
  `date_warranty_end` date DEFAULT NULL,
  `warranty_type` varchar(128) NOT NULL DEFAULT '0',
  `admin_login` varchar(128) DEFAULT NULL,
  `admin_pword` varchar(128) DEFAULT NULL,
  `note` text NOT NULL,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bh_equipment_contrat` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_object` int(10) UNSIGNED NOT NULL,
  `id_associate` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bh_inter` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_ticket` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tech_id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timer` int(10) UNSIGNED NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '1',
  `status` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bh_note` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_ticket` int(10) UNSIGNED NOT NULL,
  `id_inter` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `visibility` int(11) NOT NULL,
  `content` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED DEFAULT NULL,
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bh_ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_contrat` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ticket_number` varchar(128) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;
