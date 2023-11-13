ALTER TABLE `llx_contratdet` 
    ADD `id_parent_line` INT UNSIGNED NOT NULL DEFAULT '0',
    ADD `linked_id_object` INT UNSIGNED NOT NULL DEFAULT '0',
    ADD `linked_object_name` VARCHAR(50)  NOT NULL DEFAULT '';