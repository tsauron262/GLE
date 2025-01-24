ALTER TABLE `llx_product` 
    ADD to_rent tinyint(1) NOT NULL DEFAULT 0,
    ADD forfaits_location TEXT NOT NULL DEFAULT '',
    ADD min_qty int(11) NOT NULL DEFAULT 0;
