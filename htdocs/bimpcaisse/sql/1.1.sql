ALTER TABLE `llx_bc_vente` ADD `id_selected_location` int(11) NOT NULL DEFAULT 0;

ALTER TABLE `llx_bc_vente_article` 
    ADD `infos` TEXT NOT NULL DEFAULT '',
    ADD `linked_object_name` VARCHAR(255) NOT NULL DEFAULT '',
    ADD `linked_id_object` int(11) NOT NULL DEFAULT 0;