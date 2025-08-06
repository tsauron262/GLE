ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_received_not_billed` INT;
ALTER TABLE `llx_bimp_commande_fourn_line` CHANGE `receptions` `receptions` LONGTEXT NOT NULL;

UPDATE `llx_bimp_commande_fourn_line` SET `qty_received_not_billed` = (`qty_received` - `qty_billed`);
