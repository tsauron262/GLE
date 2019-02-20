CREATE TABLE IF NOT EXISTS `llx_bf_demande_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_parent` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `qty` decimal(24,8) NOT NULL DEFAULT '0.00000000',
  `pu_ht` decimal(24,8) NOT NULL DEFAULT '0.00000000',
  `tva_tx` decimal(8,8) NOT NULL,
  `pa_ht` decimal(24,8) NOT NULL,
  `id_fourn_price` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_fournisseur` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `equipments` text NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0'  
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `llx_bf_demande_line` ADD `label` VARCHAR(256) NOT NULL DEFAULT '' AFTER `type`;
ALTER TABLE `llx_bf_demande_line` CHANGE `tva_tx` `tva_tx` DECIMAL(8,2) NOT NULL;
