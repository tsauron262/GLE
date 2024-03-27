ALTER TABLE `llx_bimp_apple_internal_stock` 
    ADD `qty_to_receive` int(11) NOT NULL DEFAULT 0,
    ADD `do_not_order` tinyint(1) NOT NULL DEFAULT 0,
    ADD `date_last_vente` DATE DEFAULT NULL;