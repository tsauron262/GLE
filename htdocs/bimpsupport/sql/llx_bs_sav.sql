CREATE TABLE `llx_bs_apple_part` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sav` int(10) unsigned NOT NULL DEFAULT 0,
  `label` varchar(256) NOT NULL DEFAULT '',
  `part_number` varchar(128) NOT NULL DEFAULT '',
  `comptia_code` varchar(128) NOT NULL DEFAULT '',
  `comptia_modifier` varchar(128) NOT NULL DEFAULT '',
  `qty` int(10) unsigned NOT NULL DEFAULT 1,
  `component_code` varchar(128) NOT NULL DEFAULT '',
  `stock_price` float NOT NULL DEFAULT 0,
  `out_of_warranty` tinyint(1) NOT NULL DEFAULT 0,
  `exchange_price` float NOT NULL DEFAULT 0,
  `price_options` text NOT NULL DEFAULT '',
  `price_type` varchar(128) NOT NULL DEFAULT '',
  `no_order` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Affichage de la table llx_bs_inter
# ------------------------------------------------------------

CREATE TABLE `llx_bs_inter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ticket` int(10) unsigned NOT NULL DEFAULT 0,
  `tech_id_user` int(10) unsigned NOT NULL DEFAULT 0,
  `timer` int(10) unsigned NOT NULL,
  `priorite` int(11) NOT NULL DEFAULT 1,
  `status` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `resolution` text NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Affichage de la table llx_bs_note
# ------------------------------------------------------------

CREATE TABLE `llx_bs_note` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ticket` int(10) unsigned NOT NULL,
  `id_inter` int(10) unsigned NOT NULL DEFAULT 0,
  `visibility` int(11) NOT NULL,
  `content` text NOT NULL,
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(10) unsigned DEFAULT NULL,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Affichage de la table llx_bs_sav
# ------------------------------------------------------------

CREATE TABLE `llx_bs_sav` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(128) NOT NULL DEFAULT '',
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `code_centre` varchar(128) NOT NULL DEFAULT '',
  `id_equipment` int(10) unsigned NOT NULL DEFAULT 0,
  `id_entrepot` int(10) unsigned NOT NULL DEFAULT 0,
  `id_user_tech` int(10) unsigned NOT NULL DEFAULT 0,
  `id_client` int(10) unsigned NOT NULL DEFAULT 0,
  `id_contact` int(10) unsigned NOT NULL DEFAULT 0,
  `id_contrat` int(10) unsigned NOT NULL DEFAULT 0,
  `id_propal` int(10) unsigned NOT NULL DEFAULT 0,
  `id_facture_acompte` int(10) unsigned NOT NULL DEFAULT 0,
  `id_facture` int(10) unsigned NOT NULL DEFAULT 0,
  `prioritaire` tinyint(1) NOT NULL DEFAULT 0,
  `sav_pro` tinyint(1) NOT NULL DEFAULT 0,
  `prestataire_number` varchar(256) NOT NULL DEFAULT '',
  `date_problem` datetime DEFAULT NULL,
  `date_close` datetime DEFAULT NULL,
  `accident` tinyint(1) NOT NULL DEFAULT 0,
  `save_option` int(11) NOT NULL DEFAULT 0,
  `contact_pref` int(11) NOT NULL DEFAULT 0,
  `etat_materiel` int(11) NOT NULL DEFAULT 0,
  `etat_materiel_desc` text NOT NULL,
  `accessoires` text NOT NULL,
  `symptomes` text NOT NULL,
  `diagnostic` text NOT NULL,
  `resolution` text NOT NULL,
  `extra_infos` text NOT NULL,
  `system` int(11) unsigned NOT NULL DEFAULT 0,
  `login_admin` varchar(256) NOT NULL DEFAULT '',
  `pword_admin` varchar(256) NOT NULL DEFAULT '',
  `acompte` float NOT NULL DEFAULT 0,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(10) unsigned NOT NULL DEFAULT 0,
  `id_discount` int(11) DEFAULT NULL,
  `date_create_year` int(4) GENERATED ALWAYS AS (year(`date_create`)) STORED,
  `date_create_quarter` int(1) GENERATED ALWAYS AS (quarter(`date_create`)) STORED,
  `date_create_month` int(2) GENERATED ALWAYS AS (month(`date_create`)) STORED,
  `date_create_day` int(2) GENERATED ALWAYS AS (dayofmonth(`date_create`)) STORED,
  `version` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `FactS` (`id_facture_acompte`,`id_facture`),
  KEY `factureA` (`id_facture_acompte`),
  KEY `id_fact` (`id_facture`),
  KEY `idx_propal` (`id_propal`),
  KEY `idx_entrepot` (`id_entrepot`),
  KEY `idx_date_create_year` (`date_create_year`),
  KEY `idx_date_create_quarter` (`date_create_quarter`),
  KEY `idx_date_create_month` (`date_create_month`),
  KEY `idx_date_create_day` (`date_create_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Affichage de la table llx_bs_sav_pret
# ------------------------------------------------------------

CREATE TABLE `llx_bs_sav_pret` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sav` int(10) unsigned NOT NULL DEFAULT 0,
  `code_centre` varchar(12) NOT NULL DEFAULT '',
  `ref` varchar(128) NOT NULL DEFAULT '',
  `id_client` int(10) unsigned NOT NULL DEFAULT 0,
  `date_begin` datetime NOT NULL DEFAULT current_timestamp(),
  `date_end` datetime DEFAULT NULL,
  `returned` tinyint(1) NOT NULL DEFAULT 0,
  `user_create` int(11) NOT NULL DEFAULT 0,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(11) NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Affichage de la table llx_bs_sav_product
# ------------------------------------------------------------

CREATE TABLE `llx_bs_sav_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sav` int(10) unsigned NOT NULL DEFAULT 0,
  `id_product` int(10) unsigned NOT NULL DEFAULT 0,
  `id_equipment` int(10) unsigned NOT NULL DEFAULT 0,
  `id_reservation` int(10) unsigned NOT NULL DEFAULT 0,
  `qty` int(10) unsigned NOT NULL DEFAULT 0,
  `out_of_warranty` tinyint(1) NOT NULL DEFAULT 0,
  `remise` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Affichage de la table llx_bs_sav_propal_line
# ------------------------------------------------------------

CREATE TABLE `llx_bs_sav_propal_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_obj` int(10) unsigned NOT NULL DEFAULT 0,
  `id_line` int(10) unsigned NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 0,
  `deletable` tinyint(1) NOT NULL DEFAULT 1,
  `editable` tinyint(1) NOT NULL DEFAULT 1,
  `linked_id_object` int(10) unsigned NOT NULL DEFAULT 0,
  `linked_object_name` varchar(128) NOT NULL DEFAULT '',
  `id_reservation` int(10) unsigned NOT NULL DEFAULT 0,
  `out_of_warranty` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  `remise` float NOT NULL DEFAULT 0,
  `def_pu_ht` float NOT NULL DEFAULT 0,
  `def_tva_tx` float NOT NULL DEFAULT 0,
  `def_id_fourn_price` int(10) unsigned NOT NULL DEFAULT 0,
  `remisable` tinyint(1) NOT NULL DEFAULT 1,
  `force_qty_1` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Affichage de la table llx_bs_ticket
# ------------------------------------------------------------

CREATE TABLE `llx_bs_ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_contrat` int(10) unsigned NOT NULL DEFAULT 0,
  `id_client` int(10) unsigned NOT NULL DEFAULT 0,
  `id_contact` int(10) unsigned NOT NULL DEFAULT 0,
  `id_user_resp` int(10) unsigned NOT NULL DEFAULT 0,
  `ticket_number` varchar(128) NOT NULL,
  `priorite` int(10) unsigned NOT NULL DEFAULT 1,
  `impact` int(11) NOT NULL DEFAULT 1,
  `appels_timer` int(11) NOT NULL DEFAULT 0,
  `cover` int(10) unsigned NOT NULL DEFAULT 1,
  `status` int(10) unsigned NOT NULL DEFAULT 1,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(10) unsigned NOT NULL DEFAULT 0,
  `sujet` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
