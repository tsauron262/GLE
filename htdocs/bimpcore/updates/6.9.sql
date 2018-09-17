ALTER TABLE `llx_bs_ticket` ADD `sujet` TEXT(100) NULL;
ALTER TABLE `llx_bc_vente` ADD `id_avoir` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_facture`;

