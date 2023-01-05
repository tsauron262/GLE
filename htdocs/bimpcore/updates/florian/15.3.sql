
ALTER TABLE `llx_bc_caisse_mvt` ADD `id_caisse_session` INT NOT NULL DEFAULT '0' AFTER `id_caisse`; 
ALTER TABLE `llx_object_line_remise` ADD `is_remise_globale` BOOLEAN NOT NULL DEFAULT FALSE AFTER `per_unit`; 