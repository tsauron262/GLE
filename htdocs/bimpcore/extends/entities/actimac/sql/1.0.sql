CREATE TABLE IF NOT EXISTS `llx_c_famille_produit` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` int(11) NOT NULL,
  `label` int(11) NOT NULL
);

ALTER TABLE `llx_product` ADD `id_famille` int(11) NOT NULL DEFAULT 0;