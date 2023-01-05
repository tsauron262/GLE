ALTER TABLE `llx_entrepot` ADD `has_entrepot_commissions` BOOLEAN NOT NULL DEFAULT FALSE; 
ALTER TABLE `llx_entrepot` ADD `has_users_commissions` BOOLEAN NOT NULL DEFAULT TRUE; 
UPDATE `llx_entrepot` SET `has_entrepot_commissions` = 1 WHERE `has_commissions` = 1;
UPDATE `llx_entrepot` SET `has_users_commissions` = 0 WHERE `has_commissions` = 1;
ALTER TABLE `llx_entrepot` DROP `has_commissions`;

ALTER TABLE `llx_bimp_revalorisation` ADD `id_entrepot_commission` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_facture_line`; 
ALTER TABLE `llx_bimp_revalorisation` ADD `id_user_commission` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_facture_line`; 

ALTER TABLE `llx_facture` ADD `id_entrepot_commission` INT NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_facture` ADD `id_user_commission` INT NOT NULL DEFAULT '0'; 

UPDATE `llx_bimp_revalorisation` r SET r.`id_user_commission` = r.`id_commission` WHERE r.`id_commission` IN (SELECT c.id FROM llx_bimp_commission c WHERE c.type = 1);
UPDATE `llx_bimp_revalorisation` r SET r.`id_entrepot_commission` = r.`id_commission` WHERE r.`id_commission` IN (SELECT c.id FROM llx_bimp_commission c WHERE c.type = 2);

UPDATE `llx_facture` f SET f.`id_user_commission` = f.`id_commission` WHERE f.`id_commission` IN (SELECT c.id FROM llx_bimp_commission c WHERE c.type = 1);
UPDATE `llx_facture` f SET f.`id_entrepot_commission` = f.`id_commission` WHERE f.`id_commission` IN (SELECT c.id FROM llx_bimp_commission c WHERE c.type = 2);

ALTER TABLE `llx_bimp_revalorisation` DROP `id_commission`;
ALTER TABLE `llx_facture` DROP `id_commission`;

UPDATE `llx_facture` SET `id_user_commission` = -1 WHERE `datec` < '2019-07-01 00:00:00';
UPDATE `llx_facture` SET `id_entrepot_commission` = -1 WHERE `datec` < '2019-07-01 00:00:00';

ALTER TABLE `llx_bimp_propal_line` ADD `remise_crt_percent` DECIMAL(24,4) NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_commande_line` ADD `remise_crt_percent` DECIMAL(24,4) NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_facture_line` ADD `remise_crt_percent` DECIMAL(24,4) NOT NULL DEFAULT '0';