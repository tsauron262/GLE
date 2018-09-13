
CREATE TABLE IF NOT EXISTS `llx_bc_vente_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_vente` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_equipment` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` int(11) NOT NULL DEFAULT '0',
  `unit_price_tax_ex` float NOT NULL DEFAULT '0',
  `unit_price_tax_in` float NOT NULL DEFAULT '0',
  `tva_tx` float NOT NULL DEFAULT '0',
  `defective` BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB;

ALTER TABLE `llx_bc_vente` ADD `id_avoir` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_facture`;