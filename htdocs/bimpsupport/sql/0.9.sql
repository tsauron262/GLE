ALTER TABLE `llx_bh_ticket` RENAME TO llx_bs_ticket;
ALTER TABLE `llx_bh_inter` RENAME TO llx_bs_inter;
ALTER TABLE `llx_bh_note` RENAME TO llx_bs_note;

CREATE TABLE IF NOT EXISTS `llx_bs_sav` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ref` varchar(128) NOT NULL DEFAULT '',
  `status` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_equipment` INT UNSIGNED NOT NULL DEFAULT '0',
  `id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_user_tech` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_contact` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_contrat` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_propal` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_facture` INT UNSIGNED NOT NULL DEFAULT '0',
  `prioritaire` tinyint(1) NOT NULL DEFAULT '0',
  `cover` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `sav_pro` tinyint(1) NOT NULL DEFAULT '0',
  `prestataire_number` varchar(256) NOT NULL DEFAULT '',
  `date_problem` datetime DEFAULT NULL,
  `date_close` datetime DEFAULT NULL,
  `accident` tinyint(1) NOT NULL DEFAULT '0',
  `save_option` int(11) NOT NULL DEFAULT '0',
  `contact_pref` int(11) NOT NULL DEFAULT '0',
  `etat_materiel` int(11) NOT NULL DEFAULT '0',
  `etat_materiel_desc` text NOT NULL,
  `accessoires` text NOT NULL,
  `symptomes` text NOT NULL,
  `diagnostic` text NOT NULL,
  `resolution` text NOT NULL,
  `system` VARCHAR(256) NOT NULL DEFAULT '',
  `login_admin` VARCHAR(256) NOT NULL DEFAULT '',
  `pword_admin` VARCHAR(256) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


ALTER TABLE `llx_bs_sav` ADD `id_facture_acompte` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_propal`;

CREATE TABLE IF NOT EXISTS `llx_bs_apple_part` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `label` varchar(256) NOT NULL DEFAULT '',
  `part_number` varchar(128) NOT NULL DEFAULT '',
  `comptia_code` varchar(128) NOT NULL DEFAULT '',
  `comptia_modifier` varchar(128) NOT NULL DEFAULT '',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `component_code` varchar(128) NOT NULL DEFAULT '',
  `stock_price` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bs_sav_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_sav` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;



ALTER TABLE `llx_bs_apple_part` ADD `id_sav` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;

ALTER TABLE `llx_bs_sav` ADD `code_centre` VARCHAR(128) NOT NULL DEFAULT '' AFTER `status`;


ALTER TABLE `llx_bs_apple_part` ADD `no_order` BOOLEAN NOT NULL DEFAULT FALSE AFTER `stock_price`;
ALTER TABLE `llx_bs_apple_part` ADD `exchange_price` FLOAT NOT NULL DEFAULT '0' AFTER `stock_price`;
ALTER TABLE `llx_bs_apple_part` ADD `out_of_warranty` BOOLEAN NOT NULL DEFAULT FALSE AFTER `stock_price`;

ALTER TABLE `llx_bs_sav_product` ADD `out_of_warranty` BOOLEAN NOT NULL DEFAULT FALSE AFTER `qty`;



ALTER TABLE `llx_bs_sav` ADD `id_discount` INT;




ALTER TABLE `llx_bs_sav` ADD `acompte` FLOAT NOT NULL DEFAULT '0' AFTER `pword_admin`;
ALTER TABLE `llx_bs_sav` DROP `cover`;

ALTER TABLE `llx_bs_sav_product` ADD `id_reservation` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`;
ALTER TABLE `llx_bs_sav_product` ADD `id_equipment` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`; 



CREATE TABLE IF NOT EXISTS `llx_bs_pret` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ref` varchar(128) NOT NULL DEFAULT '',
  `serial` varchar(128) NOT NULL DEFAULT '',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `code_centre` varchar(128) NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bs_sav_pret` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sav` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_pret` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ref` varchar(128) NOT NULL DEFAULT '',
  `id_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_begin` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_end` datetime DEFAULT NULL,
  `returned` tinyint(1) NOT NULL DEFAULT '0',
  `user_create` int(11) NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;



DROP TABLE llx_bs_pret;


ALTER TABLE `llx_bs_sav_pret` ADD `code_centre` VARCHAR(12) NOT NULL DEFAULT '' AFTER `id_sav`;
ALTER TABLE `llx_bs_sav_pret` CHANGE `id_pret` `id_equipment` INT(10) UNSIGNED NOT NULL DEFAULT '0';



ALTER TABLE `llx_bs_sav_pret` DROP `id_equipment`;


ALTER TABLE `llx_bs_sav` CHANGE `system` `system` INT(11) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `llx_bs_sav` ADD `extra_infos` TEXT NOT NULL DEFAULT '' AFTER `resolution`;


ALTER TABLE `llx_bs_sav_product` ADD `remise` INT NOT NULL DEFAULT '0';



CREATE TABLE IF NOT EXISTS `llx_bs_ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_contrat` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_contact` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_user_resp` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ticket_number` varchar(128) NOT NULL,
  `priorite` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `impact` int(11) NOT NULL DEFAULT '1',
  `appels_timer` int(11) NOT NULL DEFAULT '0',
  `cover` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `status` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `llx_bs_inter` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_ticket` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tech_id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timer` int(10) UNSIGNED NOT NULL,
  `priorite` int(11) NOT NULL DEFAULT '1',
  `status` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED DEFAULT NULL
);


CREATE TABLE IF NOT EXISTS `llx_bs_note` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_ticket` int(10) UNSIGNED NOT NULL,
  `id_inter` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `visibility` int(11) NOT NULL,
  `content` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED DEFAULT NULL,
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);



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



ALTER TABLE `llx_bs_sav` ADD `version` FLOAT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bs_sav_propal_line` ADD `def_id_fourn_price` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bs_sav_propal_line` ADD `def_tva_tx` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bs_sav_propal_line` ADD `def_pu_ht` FLOAT NOT NULL DEFAULT '0' AFTER `position`;


ALTER TABLE `llx_bs_sav_propal_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;

ALTER TABLE `llx_bs_ticket` ADD `sujet` VARCHAR(128) NOT NULL DEFAULT '' AFTER `status`;



ALTER TABLE `llx_bs_sav_propal_line` ADD `remisable` BOOLEAN NOT NULL DEFAULT TRUE;



ALTER TABLE `llx_bs_inter` ADD `resolution` TEXT NOT NULL DEFAULT '' AFTER `description`;


ALTER TABLE `llx_bs_apple_part` ADD `price_type` VARCHAR(128) NOT NULL DEFAULT '' AFTER `exchange_price`;
ALTER TABLE `llx_bs_apple_part` ADD `price_options` TEXT NOT NULL DEFAULT '' AFTER `exchange_price`;

UPDATE `llx_bs_apple_part` SET `price_type` = 'EXCHANGE' WHERE `no_order` = 0;
UPDATE `llx_bs_apple_part` SET `price_type` = 'STOCK' WHERE `no_order` = 1;


ALTER TABLE `llx_bs_sav_propal_line` ADD `force_qty_1` BOOLEAN NOT NULL;
