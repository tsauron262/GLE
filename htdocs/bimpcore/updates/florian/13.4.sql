
ALTER TABLE `llx_bs_sav_pret` RENAME TO llx_bs_pret;

ALTER TABLE `llx_bs_pret` CHANGE `id_client` `id_client` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `llx_bs_pret` ADD `id_entrepot` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_sav`; 
ALTER TABLE `llx_bs_pret` ADD `caution` BOOLEAN NOT NULL DEFAULT FALSE AFTER `returned`; 

CREATE TABLE IF NOT EXISTS `llx_bs_pret_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_pret` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `id_product` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `qty` int(11) NOT NULL DEFAULT 0
);