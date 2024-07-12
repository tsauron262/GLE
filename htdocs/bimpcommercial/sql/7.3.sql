ALTER TABLE llx_commande_fournisseur 
    ADD `closed` tinyint NOT NULL DEFAULT 0,
    ADD `date_closed` DATETIME DEFAULT NULL;