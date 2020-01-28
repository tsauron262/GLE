ALTER TABLE `llx_bs_sav` ADD `id_facture_avoir` INT NOT NULL AFTER `id_facture`;

UPDATE llx_bs_sav s SET id_facture_avoir = (SELECT `fk_facture_source` FROM `llx_societe_remise_except` WHERE `fk_facture` = s.id_facture) WHERE id_facture_avoir = 0;