

CREATE TABLE IF NOT EXISTS `llx_bimp_commande_line` (
														`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`id_line` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`type` int(11) NOT NULL DEFAULT '0',
	`deletable` tinyint(1) NOT NULL DEFAULT '1',
	`editable` tinyint(1) NOT NULL DEFAULT '1',
	`linked_id_object` int(11) NOT NULL DEFAULT '0',
	`linked_object_name` varchar(255) NOT NULL DEFAULT '',
	`position` INT NOT NULL DEFAULT '0',
	`qty_shipped` INT NOT NULL DEFAULT '0'
	) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `llx_bl_commande_fourn_reception` (
																 `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`id_commande_fourn` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`ref` varchar(128) NOT NULL DEFAULT '',
	`num_reception` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`status` int(11) NOT NULL DEFAULT '0',
	`date_received` datetime DEFAULT NULL,
	`info` text NOT NULL,
	`user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`date_create` datetime DEFAULT CURRENT_TIMESTAMP,
	`user_update` int(11) NOT NULL DEFAULT '0',
	`date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS `llx_br_commande_shipment` (
														  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`id_commande_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`num_livraison` int(10) UNSIGNED NOT NULL DEFAULT '1',
	`qty` int(10) UNSIGNED NOT NULL DEFAULT '1',
	`date` datetime DEFAULT NULL
	) ENGINE=InnoDB;

ALTER TABLE `llx_bimp_commande_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bimp_facture_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bimp_propal_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;

ALTER TABLE `llx_bimp_propal_line` CHANGE `remise` `remise` FLOAT(24,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_propal_line` ADD `remisable` BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE `llx_bimp_facture_line` ADD `remisable` BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE `llx_bimp_commande_line` ADD `remisable` BOOLEAN NOT NULL DEFAULT TRUE;

ALTER TABLE `llx_propal` ADD `remise_globale` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_facture` ADD `remise_globale` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande` ADD `remise_globale` DOUBLE(24,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_propal_line` ADD UNIQUE( `id_line`);


ALTER TABLE `llx_bimp_commande_line` ADD `qty_billed_not_shipped` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_shipped_not_billed` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_line` DROP `qty_shipped`;

ALTER TABLE `llx_bimp_commande_line` ADD `ref_reservations` VARCHAR(128) NOT NULL DEFAULT '' AFTER `remise`;
ALTER TABLE `llx_bimp_commande_line` ADD `factures` TEXT NOT NULL;
ALTER TABLE `llx_bimp_commande_line` ADD `shipments` TEXT NOT NULL;

CREATE TABLE IF NOT EXISTS `llx_bimp_commande_fourn_line` (
															  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`id_obj` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`id_line` INT UNSIGNED NOT NULL DEFAULT '0',
	`type` int(11) NOT NULL DEFAULT '0',
	`deletable` tinyint(1) NOT NULL DEFAULT '1',
	`editable` tinyint(1) NOT NULL DEFAULT '1',
	`remisable` tinyint(1) NOT NULL DEFAULT '1',
	`linked_id_object` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`linked_object_name` varchar(256) NOT NULL,
	`remise` decimal(24,8) NOT NULL DEFAULT '0.00000000',
	`position` INT NOT NULL DEFAULT '0'
);

ALTER TABLE `llx_bimp_commande_fourn_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bimp_commande_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bimp_facture_fourn_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bimp_facture_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;
ALTER TABLE `llx_bimp_propal_line` ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_line`;



ALTER TABLE `llx_propal` ADD `remise_globale_label` TEXT NULL DEFAULT NULL;
ALTER TABLE `llx_commande` ADD `remise_globale_label` TEXT NULL DEFAULT NULL;
ALTER TABLE `llx_facture` ADD `remise_globale_label` TEXT NULL DEFAULT NULL;

ALTER TABLE `llx_bimp_commande_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_propal_line` ADD `hide_product_label` TEXT;
ALTER TABLE `llx_bimp_commande_line` ADD `hide_product_label` TEXT;
ALTER TABLE `llx_bimp_facture_line` ADD `hide_product_label` TEXT;
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `hide_product_label` TEXT;
ALTER TABLE `llx_bimp_facture_fourn_line` ADD `hide_product_label` TEXT;


ALTER TABLE `llx_bimp_commande_fourn_line` ADD `receptions` LONGTEXT NOT NULL;

ALTER TABLE `llx_commande_fournisseur` ADD `fk_user_resp` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande_fournisseur` ADD `attente_info` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_commande_fournisseur` ADD `invoice_status` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_commande` ADD `shipment_status` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande` ADD `invoice_status` INT NOT NULL DEFAULT '0';


ALTER TABLE `llx_bl_commande_fourn_reception` ADD `assign_lines_to_commandes_client` BOOLEAN NOT NULL DEFAULT TRUE AFTER `info`;
ALTER TABLE `llx_bimp_commande_line` ADD `qty_modif` DECIMAL(24,3) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_modif` DECIMAL(24,3) NOT NULL DEFAULT '0';

ALTER TABLE `llx_commande` ADD `logistique_status` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_commande` ADD `id_user_resp` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_line` ADD `remise_pa` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_propal_line` ADD `remise_pa` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` ADD `remise_pa` DECIMAL(24,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_propal_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` ADD `remise_crt` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_propal_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_fourn_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_fourn_line` CHANGE `remise` `remise` DOUBLE(24,8) NOT NULL DEFAULT '0';


ALTER TABLE `llx_bimp_commande_line` ADD `equipments_returned` TEXT NOT NULL;

ALTER TABLE `llx_bimp_commande_line` ADD `qty_total` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_shipped` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_to_ship` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_billed` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_to_bill` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_total` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_received` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_to_receive` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_revalorisation` ADD `id_entrepot_commission` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_facture_line`;
ALTER TABLE `llx_bimp_revalorisation` ADD `id_user_commission` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_facture_line`;

ALTER TABLE `llx_bimp_revalorisation` ADD INDEX(`id_user_commission`);
ALTER TABLE `llx_bimp_revalorisation` ADD INDEX(`id_entrepot_commission`);

ALTER TABLE `llx_facture` ADD `id_entrepot_commission` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_facture` ADD `id_user_commission` INT NOT NULL DEFAULT '0';

UPDATE `llx_bimp_revalorisation` r SET r.`id_user_commission` = r.`id_commission` WHERE r.`id_commission` IN (SELECT c.id FROM llx_bimp_commission c WHERE c.type = 1);
UPDATE `llx_bimp_revalorisation` r SET r.`id_entrepot_commission` = r.`id_commission` WHERE r.`id_commission` IN (SELECT c.id FROM llx_bimp_commission c WHERE c.type = 2);

UPDATE `llx_facture` f SET f.`id_user_commission` = f.`id_commission` WHERE f.`id_commission` IN (SELECT c.id FROM llx_bimp_commission c WHERE c.type = 1);
UPDATE `llx_facture` f SET f.`id_entrepot_commission` = f.`id_commission` WHERE f.`id_commission` IN (SELECT c.id FROM llx_bimp_commission c WHERE c.type = 2);

ALTER TABLE `llx_bimp_revalorisation` DROP `id_commission`;
ALTER TABLE `llx_facture` DROP `id_commission`;

UPDATE `llx_facture` SET `id_user_commission` = -1 WHERE `datec` < '2019-07-01 00:00:00';
UPDATE `llx_facture` SET `id_entrepot_commission` = -1 WHERE `datec` < '2019-07-01 00:00:00';

ALTER TABLE `llx_bimp_propal_line` ADD `remise_crt_percent` DECIMAL(24,4) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `remise_crt_percent` DECIMAL(24,4) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_facture_line` ADD `remise_crt_percent` DECIMAL(24,4) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_line` CHANGE `shipments` `shipments` LONGTEXT  NOT NULL;
ALTER TABLE `llx_bimp_commande_line` CHANGE `factures` `factures` LONGTEXT NOT NULL;
ALTER TABLE `llx_bimp_commande_line` CHANGE `equipments_returned` `equipments_returned` LONGTEXT NOT NULL;


ALTER TABLE `llx_bimp_propal_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_propal_line` ADD INDEX(`id_line`);


ALTER TABLE `llx_bimp_commande_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_commande_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_bimp_facture_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_facture_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_bimp_commande_fourn_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_commande_fourn_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_bimp_facture_fourn_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_facture_fourn_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_object_line_equipment` ADD INDEX( `id_object_line`, `object_type`);
ALTER TABLE `llx_object_line_equipment` ADD INDEX( `id_equipment`);

ALTER TABLE `llx_bl_commande_fourn_reception` ADD INDEX( `id_commande_fourn`);

ALTER TABLE `llx_bimp_propal_line` ADD `hide_in_pdf` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_bimp_commande_line` ADD `hide_in_pdf` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_bimp_facture_line` ADD `hide_in_pdf` BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE `llx_bimp_commande_line` ADD `echeance_notif_send` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `llx_bimp_commande_line` ADD `force_qty_1` BOOLEAN NOT NULL;
ALTER TABLE `llx_bimp_propal_line` ADD `force_qty_1` BOOLEAN NOT NULL;
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `force_qty_1` BOOLEAN NOT NULL;
ALTER TABLE `llx_bimp_facture_line` ADD `force_qty_1` BOOLEAN NOT NULL;
ALTER TABLE `llx_bimp_facture_fourn_line` ADD `force_qty_1` BOOLEAN NOT NULL;


ALTER TABLE `llx_br_commande_shipment` ADD `total_ttc` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_br_commande_shipment` ADD `total_ht` DECIMAL(24,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bl_commande_fourn_reception` ADD `total_ttc` DECIMAL(24,8) NOT NULL DEFAULT '0' AFTER `assign_lines_to_commandes_client`;
ALTER TABLE `llx_bl_commande_fourn_reception` ADD `total_ht` DECIMAL(24,8) NOT NULL DEFAULT '0' AFTER `assign_lines_to_commandes_client`;


ALTER TABLE `llx_commande` ADD `extra_status` INT NOT NULL DEFAULT '0' AFTER `logistique_status`;

ALTER TABLE `llx_br_commande_shipment` ADD `id_user_resp` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bl_commande_fourn_reception` ADD `id_user_resp` INT NOT NULL DEFAULT '0' AFTER `total_ttc`;

ALTER TABLE `llx_bl_commande_fourn_reception` ADD `id_facture` INT NOT NULL DEFAULT '0' AFTER `id_user_resp`;


ALTER TABLE `llx_bl_commande_fourn_reception` CHANGE `date_received` `date_received` DATE NULL DEFAULT NULL;



ALTER TABLE `llx_br_reservation_shipment` ADD `id_commande_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `llx_br_reservation_shipment` ADD `id_shipment` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `ref_reservation`;


ALTER TABLE `llx_br_commande_shipment` DROP `qty`;
ALTER TABLE `llx_br_commande_shipment` ADD `id_contact` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `num_livraison`;
ALTER TABLE `llx_br_commande_shipment` ADD `status` INT UNSIGNED NOT NULL DEFAULT '1' AFTER `num_livraison`;
ALTER TABLE `llx_br_commande_shipment` CHANGE `date` `date_shipped` DATETIME NULL DEFAULT NULL;

ALTER TABLE `llx_br_commande_shipment` ADD `date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `date_shipped`;
ALTER TABLE `llx_br_commande_shipment` ADD `user_update` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_shipped`;
ALTER TABLE `llx_br_commande_shipment` ADD `date_create` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `date_shipped`;
ALTER TABLE `llx_br_commande_shipment` ADD `user_create` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_shipped`;

ALTER TABLE `llx_br_commande_shipment` ADD `ref` VARCHAR(255) NOT NULL AFTER `num_livraison`;


ALTER TABLE `llx_br_commande_shipment` ADD `info` TEXT NOT NULL;
ALTER TABLE `llx_br_commande_shipment` ADD `signed` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_shipped`;
ALTER TABLE `llx_br_commande_shipment` ADD `id_facture` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `date_shipped`;
ALTER TABLE `llx_br_commande_shipment` ADD `id_avoir` INT NOT NULL DEFAULT '0' AFTER `id_facture`;

ALTER TABLE `llx_commande` ADD `id_facture` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `multicurrency_total_ttc`;


ALTER TABLE `llx_br_commande_shipment` RENAME TO llx_bl_commande_shipment;

ALTER TABLE `llx_bl_commande_shipment` ADD INDEX( `id_commande_client`);

