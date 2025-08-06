ALTER TABLE `llx_entrepot` ADD `has_entrepot_commissions` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `llx_entrepot` ADD `has_users_commissions` BOOLEAN NOT NULL DEFAULT TRUE;
UPDATE `llx_entrepot` SET `has_entrepot_commissions` = 1 WHERE `has_commissions` = 1;
UPDATE `llx_entrepot` SET `has_users_commissions` = 0 WHERE `has_commissions` = 1;
ALTER TABLE `llx_entrepot` DROP `has_commissions`;

