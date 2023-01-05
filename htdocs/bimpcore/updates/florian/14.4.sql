
ALTER TABLE `llx_br_reservation` ADD `id_origin` INT NOT NULL DEFAULT '0' AFTER `note`; 
ALTER TABLE `llx_br_reservation` ADD `origin` VARCHAR(256) NOT NULL DEFAULT '' AFTER `note`; 

ALTER TABLE `llx_commande` ADD `extra_status` INT NOT NULL DEFAULT '0' AFTER `logistique_status`; 

ALTER TABLE `llx_br_commande_shipment` ADD `id_user_resp` INT NOT NULL DEFAULT '0' AFTER `total_ttc`; 
ALTER TABLE `llx_bl_commande_fourn_reception` ADD `id_user_resp` INT NOT NULL DEFAULT '0' AFTER `total_ttc`; 