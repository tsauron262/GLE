ALTER TABLE `llx_product` 
    ADD `achat_def_periodicity` int(11) NOT NULL DEFAULT 0,
    ADD `achat_def_id_fourn` int(11) NOT NULL DEFAULT 0,
    ADD `fac_def_periodicity` int(11) NOT NULL DEFAULT 0,
    ADD `fac_def_terme` tinyint(1) NOT NULL DEFAULT 1,
    ADD `variable_qty` tinyint(1) NOT NULL DEFAULT 0,
    ADD `variable_qty_unit` VARCHAR(255) NOT NULL DEFAULT '',
    ADD `variable_qty_decimals` int(11) NOT NULL DEFAULT 0;