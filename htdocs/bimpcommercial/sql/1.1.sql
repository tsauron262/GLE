
ALTER TABLE `llx_bimp_commande_line` ADD `qty_billed_not_shipped` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_shipped_not_billed` INT NOT NULL DEFAULT '0';