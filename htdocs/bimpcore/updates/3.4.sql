ALTER TABLE `llx_be_reservation` RENAME TO llx_br_reservation;

ALTER TABLE `llx_br_reservation` CHANGE `active` `status` INT(11) NOT NULL DEFAULT '200'; 
ALTER TABLE `llx_br_reservation` CHANGE `id_user_origin` `id_commercial` INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `llx_br_reservation` DROP `origin_id_element`;
ALTER TABLE `llx_br_reservation` DROP `origin_element`;

ALTER TABLE `llx_br_reservation` ADD `id_entrepot` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `llx_br_reservation` ADD `type` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`; 
ALTER TABLE `llx_br_reservation` ADD `id_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`; 
ALTER TABLE `llx_br_reservation` ADD `id_entrepot` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;

ALTER TABLE `llx_br_reservation` ADD `id_transfert` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`;
ALTER TABLE `llx_br_reservation` ADD `id_commande_fournisseur_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`; 
ALTER TABLE `llx_br_reservation` ADD `id_commande_fournisseur` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`;
ALTER TABLE `llx_br_reservation` ADD `id_commande_client_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`;
ALTER TABLE `llx_br_reservation` ADD `id_commande_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_product`;

DROP TABLE llx_be_logiciel

ALTER TABLE `llx_be_equipment_logiciel` CHANGE `id_logiciel` `id_product` INT(10) UNSIGNED NOT NULL DEFAULT '0';