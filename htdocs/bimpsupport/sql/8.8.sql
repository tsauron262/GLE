ALTER TABLE `llx_bs_sav` ADD `restituted` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bs_sav` ADD `propal_refused` tinyint(1) NOT NULL DEFAULT 0;
UPDATE `llx_bs_sav` SET `restituted` = 1 WHERE `status` = 999;
UPDATE `llx_bs_sav` SET `propal_refused` = 1 WHERE `status` = 6;

UPDATE `llx_bs_sav` a SET a.`propal_refused` = 1 WHERE (SELECT COUNT(pl.rowid) FROM llx_propaldet pl WHERE pl.fk_propal = a.id_propal AND pl.fk_product = (SELECT p.rowid FROM llx_product p WHERE p.ref = 'SAV-REFUS')) > 0;