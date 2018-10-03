
CREATE TABLE IF NOT EXISTS `llx_bc_caisse` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(256) NOT NULL,
  `status` int(10) UNSIGNED NOT NULL,
  `id_current_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_current_session` INT NOT NULL DEFAULT '0',
  `fonds` float NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bc_caisse_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_caisse` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fonds_begin` float NOT NULL DEFAULT '0',
  `fonds_end` float NOT NULL DEFAULT '0',
  `date_open` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_closed` datetime DEFAULT NULL,
  `id_user_open` int(11) NOT NULL DEFAULT '0',
  `id_user_closed` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bc_vente` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `status` int(11) NOT NULL DEFAULT '2',
  `total_ttc` float NOT NULL DEFAULT '0',
  `total_ht` float NOT NULL DEFAULT '0',
  `id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_caisse` INT NOT NULL DEFAULT '0',
  `id_caisse_session` INT UNSIGNED NOT NULL DEFAULT '0',
  `id_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_client_contact` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bc_vente_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_vente` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_equipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `unit_price_tax_in` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_bc_vente_paiement` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_vente` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `code` varchar(12) NOT NULL DEFAULT '',
  `montant` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;