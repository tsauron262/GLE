ALTER TABlE `llx_propal` ADD `commande_status` INT(11) NOT NULL DEFAULT 0;

UPDATE `llx_propal` SET `commande_status` = 1 WHERE fk_statut = 2;
UPDATE `llx_propal` SET `commande_status` = 2 WHERE fk_statut = 4;