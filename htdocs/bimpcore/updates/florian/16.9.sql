CREATE TABLE IF NOT EXISTS `llx_be_package_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_package` int(11) NOT NULL DEFAULT 0,
  `id_product` int(11) NOT NULL DEFAULT 0,
  `qty` int(11) NOT NULL DEFAULT 0
);

ALTER TABLE `llx_be_equipment` ADD `id_package` int(11) NOT NULL DEFAULT 0 AFTER `id_facture`;
ALTER TABLE `llx_be_equipment` ADD `ref_immo` varchar(255) NOT NULL DEFAULT '' AFTER `serial`;