
INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('bimpcaisse_id_cond_reglement_default', 23);
INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('bimpcaisse_secteur_code', 'X');

ALTER TABLE `llx_bc_vente` ADD `id_cond_reglement` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_client_contact`;