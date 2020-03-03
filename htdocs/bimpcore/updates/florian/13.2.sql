ALTER TABLE `llx_br_reservation_shipment` ADD `converted` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_br_service_shipment` ADD `converted` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_br_reservation` ADD `commande_client_converted` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_commande` ADD `facture_converted` BOOLEAN NOT NULL DEFAULT FALSE;
