ALTER TABLE `llx_contratdet` 
    ADD `id_linked_line` int(11) NOT NULL DEFAULT 0,
    ADD `linked_object_name` VARCHAR(255) NOT NULL DEFAULT '',
    ADD `linked_id_object` int(11) NOT NULL DEFAULT 0;