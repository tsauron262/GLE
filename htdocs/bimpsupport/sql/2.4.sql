ALTER TABLE `llx_bs_pret` ADD `note` TEXT NOT NULL DEFAULT '' AFTER `caution`;


ALTER TABLE `llx_bs_pret` CHANGE `date_create` `date_create` datetime DEFAULT NULL;
ALTER TABLE `llx_bs_pret` CHANGE `date_update` `date_update` datetime DEFAULT NULL;
ALTER TABLE `llx_bs_pret` CHANGE `date_begin` `date_begin` DATE NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `llx_bs_pret` CHANGE `date_end` `date_end` DATE NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `llx_bs_pret` CHANGE `date_create` `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `llx_bs_pret` CHANGE `date_update` `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `llx_bs_pret` CHANGE `id_client` `id_client` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `llx_bs_pret` ADD `id_entrepot` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_sav`;
ALTER TABLE `llx_bs_pret` ADD `caution` BOOLEAN NOT NULL DEFAULT FALSE AFTER `returned`;

CREATE TABLE IF NOT EXISTS `llx_bs_pret_product` (
													 `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`id_pret` int(10) UNSIGNED NOT NULL DEFAULT 0,
	`id_product` int(10) UNSIGNED NOT NULL DEFAULT 0,
	`qty` int(11) NOT NULL DEFAULT 0
	);

