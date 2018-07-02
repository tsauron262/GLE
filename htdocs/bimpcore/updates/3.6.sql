
    ALTER TABLE `llx_br_reservation` ADD `ref` VARCHAR(128) NOT NULL AFTER `id`;
ALTER TABLE `llx_br_reservation_cmd_fourn` ADD `ref_reservation` VARCHAR(128) NOT NULL DEFAULT '' AFTER `id_reservation`;
ALTER TABLE `llx_br_reservation_cmd_fourn` ADD `id_entrepot` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `id_commande_client`;
ALTER TABLE `llx_br_reservation_cmd_fourn` ADD `special_price_tva_tx` FLOAT NOT NULL DEFAULT '0' AFTER `special_price`;