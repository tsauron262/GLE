ALTER TABLE `llx_contratdet` 
    ADD `line_origin_type` VARCHAR(255) NOT NULL DEFAULT '',
    ADD `id_line_origin` int(11) NOT NULL DEFAULT 0;