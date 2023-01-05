ALTER TABLE `llx_bimp_commande_line` CHANGE `qty_total` `qty_total` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `qty_shipped` `qty_shipped` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `qty_to_ship` `qty_to_ship` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `qty_billed` `qty_billed` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `qty_to_bill` `qty_to_bill` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `qty_billed_not_shipped` `qty_billed_not_shipped` DECIMAL(24,8) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` CHANGE `qty_shipped_not_billed` `qty_shipped_not_billed` DECIMAL(24,8) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_line` DROP `periods_start`;