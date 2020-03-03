
ALTER TABLE `llx_br_commande_shipment` ADD `id_avoir` INT NOT NULL DEFAULT '0' AFTER `id_facture`; 

ALTER TABLE `llx_br_commande_shipment` RENAME TO llx_bl_commande_shipment;