
ALTER TABLE `llx_bc_caisse` ADD `id_account` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `fonds`;
ALTER TABLE `llx_bc_caisse` DROP `id_current_user`; 
ALTER TABLE `llx_bc_vente` ADD `vente_ht` BOOLEAN NOT NULL DEFAULT FALSE AFTER `id_avoir`;

CREATE TABLE IF NOT EXISTS `llx_bc_caisse_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_caisse` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_caisse_session` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_user` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bc_paiement` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_caisse` int(11) NOT NULL DEFAULT '0',
  `id_caisse_session` int(11) NOT NULL DEFAULT '0',
  `id_facture` int(11) NOT NULL DEFAULT '0',
  `id_vente` int(11) NOT NULL DEFAULT '0',
  `id_paiement` int(11) NOT NULL DEFAULT '0',
  `user_create` int(11) NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('sav_use_caisse_for_payments', 0);
INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('bimpcaisse_id_default_account', 1);