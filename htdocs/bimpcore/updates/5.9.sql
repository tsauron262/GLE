
ALTER TABLE `llx_facture`
 ADD `codeCli8Sens` varchar(30);

ALTER TABLE `llx_facture`
 ADD `Collab8sens` varchar(30);

ALTER TABLE `llx_facture`
 ADD `Collab8sens` varchar(30);

ALTER TABLE `llx_paiement_facture` 
ADD `exported` INT NOT NULL DEFAULT '0' AFTER `multicurrency_amount`;