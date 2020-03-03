
ALTER TABLE `llx_bimp_commande_line` CHANGE `remise_pa` `remise_pa` DECIMAL(24,8) NOT NULL DEFAULT '0.00000000'; 
ALTER TABLE `llx_bimp_propal_line` CHANGE `remise_pa` `remise_pa` DECIMAL(24,8) NOT NULL DEFAULT '0.00000000'; 
ALTER TABLE `llx_bs_sav_propal_line` CHANGE `remise_pa` `remise_pa` DECIMAL(24,8) NOT NULL DEFAULT '0.00000000'; 
ALTER TABLE `llx_bimp_facture_line` CHANGE `remise_pa` `remise_pa` DECIMAL(24,8) NOT NULL DEFAULT '0.00000000'; 