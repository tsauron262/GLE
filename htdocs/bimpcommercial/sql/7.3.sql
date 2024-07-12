ALTER TABLE llx_commande_fournisseur 
    ADD `closed` tinyint NOT NULL DEFAULT 0,
    ADD `date_closed` DATETIME DEFAULT NULL;

UPDATE llx_commande_fournisseur SET closed = 1, date_closed = tms WHERE fk_statut IN (6,7,9);
UPDATE llx_commande_fournisseur SET closed = 1, date_closed = tms WHERE fk_statut = 5 AND invoice_status = 2;