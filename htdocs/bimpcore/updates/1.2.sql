
INSERT INTO `llx_bimpcore_conf` (`name`, `value`) VALUES ('bimpcore_version', '1.1');

ALTER TABLE `llx_bf_demande` 
ADD `terme_paiement` INT NOT NULL DEFAULT '2' AFTER `vr`, 
ADD `status` INT NOT NULL DEFAULT '0', 
ADD `accepted` BOOLEAN NOT NULL DEFAULT FALSE,
ADD `id_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`, 
ADD `id_client_contact` INT UNSIGNED NOT NULL DEFAULT '0',
ADD `montant_materiels` FLOAT NOT NULL DEFAULT '0.00' AFTER `vr`, 
ADD `montant_services` FLOAT NOT NULL DEFAULT '0.00' AFTER `vr`, 
ADD `montant_logiciels` FLOAT NOT NULL DEFAULT '0.00' AFTER `vr`,
ADD `commission_commerciale` FLOAT NOT NULL DEFAULT '0' AFTER `vr`, 
ADD `commission_financiere` FLOAT NOT NULL DEFAULT '0' AFTER `vr`;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;