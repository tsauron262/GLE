

CREATE TABLE IF NOT EXISTS `llx_br_reservation_cmd_fourn` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_commande_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_fournisseur` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(11) NOT NULL DEFAULT '1',
  `id_price` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `special_price` float NOT NULL DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `id_commande_fournisseur` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_commande_fournisseur_line` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

ALTER TABLE `llx_bc_vente` ADD `id_facture` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_client_contact`;

ALTER TABLE `llx_bc_vente_article` ADD `unit_price_tax_ex` FLOAT NOT NULL DEFAULT '0.0' AFTER `qty`; 
ALTER TABLE `llx_bc_vente_article` ADD `tva_tx` FLOAT NOT NULL DEFAULT '0.0' AFTER `unit_price_tax_in`;

ALTER TABLE `llx_be_equipment` ADD `id_facture` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `note`; 
ALTER TABLE `llx_be_equipment` ADD `date_vente` DATETIME NULL DEFAULT NULL AFTER `note`;
ALTER TABLE `llx_be_equipment` ADD `prix_vente` FLOAT NOT NULL DEFAULT '0' AFTER `note`;
ALTER TABLE `llx_be_equipment` ADD `prix_vente_except` FLOAT NOT NULL DEFAULT '0' AFTER `note`;

