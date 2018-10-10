
ALTER TABLE `llx_bimp_commande_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bimp_facture_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bimp_propal_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bs_sav_propal_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;

ALTER TABLE `llx_bs_ticket` ADD `sujet` VARCHAR(128) NOT NULL DEFAULT '' AFTER `status`;