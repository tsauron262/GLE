ALTER TABLE `llx_bc_vente` ADD `vente_ht` BOOLEAN NOT NULL DEFAULT FALSE AFTER `id_facture`;
ALTER TABLE `llx_bc_vente` ADD `id_avoir` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_facture`;

ALTER TABLE `llx_bs_sav` ADD `version` FLOAT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bs_sav_propal_line` ADD `def_id_fourn_price` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bs_sav_propal_line` ADD `def_tva_tx` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bs_sav_propal_line` ADD `def_pu_ht` FLOAT NOT NULL DEFAULT '0' AFTER `position`;

CREATE TABLE IF NOT EXISTS `llx_bc_vente_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_vente` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_equipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(11) NOT NULL DEFAULT '0',
  `unit_price_tax_ex` float NOT NULL DEFAULT '0',
  `unit_price_tax_in` float NOT NULL DEFAULT '0',
  `tva_tx` float NOT NULL DEFAULT '0',
  `defective` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_object_line_remise` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_object_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `object_type` varchar(128) NOT NULL DEFAULT '',
  `label` varchar(256) NOT NULL DEFAULT '',
  `type` int(11) NOT NULL DEFAULT '1',
  `percent` float NOT NULL DEFAULT '0',
  `montant` float NOT NULL DEFAULT '0',
  `per_unit` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `llx_bimp_file`
  DROP `file_dir`,
  DROP `file_type`;