ALTER TABLE `llx_br_commande_shipment` ADD `total_ttc` DECIMAL(24,8) NOT NULL DEFAULT '0' AFTER `info`; 
ALTER TABLE `llx_br_commande_shipment` ADD `total_ht` DECIMAL(24,8) NOT NULL DEFAULT '0' AFTER `info`; 

ALTER TABLE `llx_bl_commande_fourn_reception` ADD `total_ttc` DECIMAL(24,8) NOT NULL DEFAULT '0' AFTER `assign_lines_to_commandes_client`; 
ALTER TABLE `llx_bl_commande_fourn_reception` ADD `total_ht` DECIMAL(24,8) NOT NULL DEFAULT '0' AFTER `assign_lines_to_commandes_client`; 