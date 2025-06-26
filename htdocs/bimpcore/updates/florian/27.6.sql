ALTER TABLE `llx_bimpcore_note_model`
ADD `id_owner` INT(11) NOT NULL DEFAULT 0,
ADD `owner_type` INT(3) NOT NULL DEFAULT 1;

UPDATE `llx_bimpcore_note_model` SET `id_owner` = 1 WHERE id_owner = 0;
