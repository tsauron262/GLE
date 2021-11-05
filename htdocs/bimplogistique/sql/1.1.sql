ALTER TABLE `llx_bl_commande_shipment` ADD `id_signature` int(11) DEFAULT 0 NOT NULL;
ALTER TABLE `llx_bl_commande_shipment` ADD `signature_params` TEXT NOT NULL DEFAULT '';