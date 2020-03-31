ALTER TABLE `llx_facture` ADD `paiement_status` INT NOT NULL DEFAULT '0';
UPDATE `llx_facture` SET `paiement_status` = 2 WHERE paye = 1;