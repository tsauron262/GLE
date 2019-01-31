
INSERT INTO `llx_bimpcore_conf` (`name`, `value`) VALUES ('bimpcore_version', '1.1');

CREATE TABLE IF NOT EXISTS `llx_bf_demande` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_client` INT UNSIGNED NOT NULL DEFAULT '0', 
  `duration` int(10) UNSIGNED NOT NULL,
  `periodicity` int(10) UNSIGNED NOT NULL,
  `vr` int(11) NOT NULL,
  `terme_paiement` INT NOT NULL DEFAULT '2', 
  `montant_materiels` FLOAT NOT NULL DEFAULT '0.00', 
  `montant_services` FLOAT NOT NULL DEFAULT '0.00', 
  `montant_logiciels` FLOAT NOT NULL DEFAULT '0.00',
  `commission_commerciale` FLOAT NOT NULL DEFAULT '0', 
  `commission_financiere` FLOAT NOT NULL DEFAULT '0',
  `status` INT NOT NULL DEFAULT '0', 
  `accepted` BOOLEAN NOT NULL DEFAULT FALSE,
  `id_client_contact` INT UNSIGNED NOT NULL DEFAULT '0',
  `insurance` tinyint(1) NOT NULL DEFAULT '0',
  `annexe` int(11) NOT NULL DEFAULT '0',
  `ca_prevu` float NOT NULL DEFAULT '0',
  `pba_prevu` float NOT NULL DEFAULT '0',
  `date_livraison` date DEFAULT NULL,
  `date_loyer` date DEFAULT NULL,
  `id_supplier` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_supplier_contact` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bf_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_demande` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `user_create` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bf_refinanceur` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_demande` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bf_rent` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_demande` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `amount_ht` float NOT NULL DEFAULT '0',
  `payment` int(11) NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bf_rent_except` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_demande` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `amount` float NOT NULL DEFAULT '0',
  `payment` int(11) NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bf_frais_divers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_demande` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL DEFAULT '0000-00-00',
  `description` text NOT NULL,
  `amount` float NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bf_frais_fournisseur` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_demande` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `id_soc_supplier` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `supplier_name` varchar(256) NOT NULL DEFAULT '',
  `description` varchar(256) NOT NULL DEFAULT '',
  `amount` float NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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

CREATE TABLE IF NOT EXISTS `llx_bimp_timer` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_module` varchar(128) NOT NULL,
  `obj_name` varchar(128) NOT NULL,
  `id_obj` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(128) NOT NULL,
  `time_session` int(11) NOT NULL,
  `session_start` int(11) DEFAULT NULL,
  `id_user` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB;
