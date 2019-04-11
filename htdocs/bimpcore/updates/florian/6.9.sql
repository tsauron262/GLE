
ALTER TABLE `llx_bimp_commande_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bimp_facture_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
ALTER TABLE `llx_bimp_propal_line` ADD `remise` FLOAT NOT NULL DEFAULT '0' AFTER `position`;
