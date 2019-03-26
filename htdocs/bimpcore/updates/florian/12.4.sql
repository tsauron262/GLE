
ALTER TABLE `llx_commande_fournisseur` ADD `attente_info` BOOLEAN NOT NULL DEFAULT FALSE AFTER `last_main_doc`;
ALTER TABLE `llx_commande_fournisseur` ADD `fk_user_resp` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `last_main_doc`;