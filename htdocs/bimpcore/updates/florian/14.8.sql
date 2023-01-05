ALTER TABLE `llx_bimp_commande_line` ADD `qty_total` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_shipped` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_to_ship` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_line` ADD `qty_billed` INT NOT NULL DEFAULT '0'; 
ALTER TABLE `llx_bimp_commande_line` ADD `qty_to_bill` INT NOT NULL DEFAULT '0';

ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_total` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_received` INT NOT NULL DEFAULT '0';
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_to_receive` INT NOT NULL DEFAULT '0'; 