ALTER TABLE `llx_product`
    ADD `achats_partiels_allowed` tinyint(1) NOT NULL DEFAULT 1,
    ADD `renouv_tacites_allowed` tinyint(1) NOT NULL DEFAULT 1;