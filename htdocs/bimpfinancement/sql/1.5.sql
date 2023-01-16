ALTER TABLE `llx_bf_demande` ADD `merged_demandes` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bf_demande_source` ADD `id_init_demande` int(11) NOT NULL DEFAULT 0 AFTER `id_demande`;