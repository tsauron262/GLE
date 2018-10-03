
CREATE TABLE IF NOT EXISTS `llx_be_user_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL DEFAULT '',
  `id_client_contact` INT UNSIGNED NOT NULL DEFAULT '0',
  `id_client` INT UNSIGNED NOT NULL DEFAULT '0',
  `login` varchar(128) NOT NULL DEFAULT '',
  `pword` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

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

ALTER TABLE `llx_be_equipment` ADD `product_label` VARCHAR(256) NOT NULL DEFAULT '' AFTER `id_product`;

CREATE TABLE IF NOT EXISTS `llx_bimp_note` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `obj_type` varchar(128) NOT NULL DEFAULT '',
  `obj_module` varchar(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `visibility` int(10) UNSIGNED NOT NULL DEFAULT '2',
  `content` text NOT NULL,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;