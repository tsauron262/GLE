ALTER TABLE `llx_societe` ADD `outstanding_limit_credit_safe` DECIMAL(24,8) DEFAULT 0 NOT NULL;
UPDATE `llx_societe` SET fk_typent = 5 WHERE (`siret` LIKE '1%' || `siret` LIKE '2%') AND fk_typent != 5;
UPDATE `llx_societe` SET fk_typent = 0 WHERE (`siret` NOT LIKE '1%' && `siret` NOT LIKE '2%') AND fk_typent = 5;
UPDATE `llx_societe` SET `outstanding_limit` = 50000 WHERE fk_typent = 5;
UPDATE `llx_societe` SET outstanding_limit = 0 WHERE fk_typent = 8 AND outstanding_limit > 0;
UPDATE `llx_societe` SET `status` = 0 WHERE fk_typent = 8 AND code_compta LIKE 'E%';
UPDATE `llx_societe` SET `status` = 0 WHERE fk_typent != 8 AND code_compta LIKE 'P%'

