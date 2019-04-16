ALTER TABLE `llx_be_equipment` ADD `achat_tva_tx` DECIMAL(24,3) NULL DEFAULT NULL AFTER `prix_achat`;
ALTER TABLE `llx_be_equipment` ADD `vente_tva_tx` DECIMAL(24,3) NULL DEFAULT NULL AFTER `prix_vente_except`; 

ALTER TABLE `llx_bl_commande_fourn_reception` ADD `assign_lines_to_commandes_client` BOOLEAN NOT NULL DEFAULT TRUE AFTER `info`;
ALTER TABLE `llx_bimp_commande_line` ADD `qty_modif` DECIMAL(24,3) NOT NULL DEFAULT '0' AFTER `force_qty_1`; 
ALTER TABLE `llx_bimp_commande_fourn_line` ADD `qty_modif` DECIMAL(24,3) NOT NULL DEFAULT '0' AFTER `force_qty_1`;  
