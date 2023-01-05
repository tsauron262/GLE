ALTER TABLE `llx_bimp_commission` ADD `type` INT NOT NULL DEFAULT '1' AFTER `id`; 
ALTER TABLE `llx_bimp_commission` ADD `id_entrepot` INT NOT NULL DEFAULT '0' AFTER `id_user`; 

ALTER TABLE `llx_bimp_revalorisation` DROP `id_user`;
ALTER TABLE `llx_bimp_revalorisation` DROP `applied`;

ALTER TABLE `llx_bimp_revalorisation` ADD `id_facture` INT NOT NULL DEFAULT '0' AFTER `id`; 
ALTER TABLE `llx_bimp_revalorisation` ADD `id_commission` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_facture_line`; 
ALTER TABLE `llx_bimp_revalorisation` ADD `date_processed` DATE NULL DEFAULT NULL;
ALTER TABLE `llx_bimp_revalorisation` ADD `id_user_processed` INT NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_revalorisation` ADD `qty` DECIMAL(24,4) NOT NULL DEFAULT '0' AFTER `amount`; 

ALTER TABLE `llx_facture` ADD `id_commission` INT NOT NULL DEFAULT '0'; 

UPDATE `llx_facture` SET `id_commission` = -1 WHERE `datec` < '2019-07-01 00:00:00';

ALTER TABLE `llx_entrepot` ADD `has_commissions` BOOLEAN NOT NULL DEFAULT FALSE; 
UPDATE `llx_entrepot` SET `has_commissions` = 1 WHERE `ref` LIKE 'SAV%';
