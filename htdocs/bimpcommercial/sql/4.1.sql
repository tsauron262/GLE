ALTER TABLE `llx_bimp_relance_clients` ADD `note` TEXT NOT NULL DEFAULT '';

UPDATE `llx_bimp_relance_clients` SET mode = 'global' WHERE mode IN ('auto', 'man');