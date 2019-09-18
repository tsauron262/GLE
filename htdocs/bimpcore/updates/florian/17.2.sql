ALTER TABLE `llx_be_equipment` ADD INDEX(`id_package`); 

ALTER TABLE `llx_be_equipment` ADD INDEX(`id_product`); 

ALTER TABLE `llx_be_equipment_place` ADD INDEX(`id_equipment`); 
ALTER TABLE `llx_be_equipment_place` ADD INDEX(`type`); 
ALTER TABLE `llx_be_equipment_place` ADD INDEX(`id_entrepot`); 
ALTER TABLE `llx_be_equipment_place` ADD INDEX(`id_user`); 
ALTER TABLE `llx_be_equipment_place` ADD INDEX(`id_client`); 

ALTER TABLE `llx_be_package_place` ADD INDEX(`id_package`); 
ALTER TABLE `llx_be_package_place` ADD INDEX(`type`); 
ALTER TABLE `llx_be_package_place` ADD INDEX(`id_entrepot`); 
ALTER TABLE `llx_be_package_place` ADD INDEX(`id_user`); 
ALTER TABLE `llx_be_package_place` ADD INDEX(`id_client`); 

ALTER TABLE `llx_be_package_product` ADD INDEX(`id_package`); 
ALTER TABLE `llx_be_package_product` ADD INDEX(`id_product`); 

ALTER TABLE `llx_bimp_propal_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_propal_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_bs_sav_propal_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bs_sav_propal_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_bimp_commande_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_commande_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_bimp_facture_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_facture_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_bimp_commande_fourn_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_commande_fourn_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_bimp_facture_fourn_line` ADD INDEX(`id_obj`);
ALTER TABLE `llx_bimp_facture_fourn_line` ADD INDEX(`id_line`);

ALTER TABLE `llx_object_line_remise` ADD INDEX( `id_object_line`, `object_type`); 
ALTER TABLE `llx_object_line_equipment` ADD INDEX( `id_object_line`, `object_type`); 
ALTER TABLE `llx_object_line_equipment` ADD INDEX( `id_equipment`); 

ALTER TABLE `llx_bl_commande_shipment` ADD INDEX( `id_commande_client`); 
ALTER TABLE `llx_bl_commande_fourn_reception` ADD INDEX( `id_commande_fourn`); 

ALTER TABLE `llx_bimp_commission` ADD INDEX(`id_user`);
ALTER TABLE `llx_bimp_commission` ADD INDEX(`id_entrepot`);

ALTER TABLE `llx_br_reservation` ADD INDEX(`id_product`);
ALTER TABLE `llx_br_reservation` ADD INDEX(`id_equipment`);
ALTER TABLE `llx_br_reservation` ADD INDEX(`id_commande_client`);
ALTER TABLE `llx_br_reservation` ADD INDEX(`id_commande_client_line`);

ALTER TABLE `llx_bs_sav` ADD INDEX(`id_entrepot`);
ALTER TABLE `llx_bs_apple_part` ADD INDEX(`id_sav`);

ALTER TABLE `llx_bs_inter` ADD INDEX(`id_ticket`);
ALTER TABLE `llx_bs_inter` ADD INDEX(`id_ticket`);
ALTER TABLE `llx_bs_note` ADD INDEX(`id_ticket`);
ALTER TABLE `llx_bs_note` ADD INDEX(`id_inter`);

ALTER TABLE `llx_bc_caisse_mvt` ADD INDEX(`id_entrepot`);
ALTER TABLE `llx_bc_caisse_mvt` ADD INDEX(`id_caisse`);
ALTER TABLE `llx_bc_caisse_session` ADD INDEX(`id_caisse`);

ALTER TABLE `llx_bc_vente_article` ADD INDEX(`id_vente`);
ALTER TABLE `llx_bc_vente_paiement` ADD INDEX(`id_vente`);
ALTER TABLE `llx_bc_vente_remise` ADD INDEX(`id_vente`);
ALTER TABLE `llx_bc_vente_return` ADD INDEX(`id_vente`);

ALTER TABLE `llx_bimp_revalorisation` ADD INDEX(`id_facture`);
ALTER TABLE `llx_bimp_revalorisation` ADD INDEX(`id_facture_line`);
ALTER TABLE `llx_bimp_revalorisation` ADD INDEX(`id_user_commission`);
ALTER TABLE `llx_bimp_revalorisation` ADD INDEX(`id_entrepot_commission`);

ALTER TABLE `llx_bimpcore_correctif` ADD INDEX( `obj_type`, `obj_module`, `obj_name`, `id_obj`); 
ALTER TABLE `llx_bimpcore_file` ADD INDEX( `parent_module`, `parent_object_name`, `id_parent`); 
ALTER TABLE `llx_bimpcore_history` ADD INDEX( `module`, `object`, `id_object`); 
ALTER TABLE `llx_bimpcore_list_config` ADD INDEX( `id_owner`, `obj_module`, `obj_name`, `list_name`); 
ALTER TABLE `llx_bimpcore_list_filters` ADD INDEX( `id_owner`, `obj_module`, `obj_name`, `list_type`, `list_name`); 
ALTER TABLE `llx_bimpcore_note` ADD INDEX( `obj_type`, `obj_module`, `obj_name`, `id_obj`); 
ALTER TABLE `llx_bimpcore_timer` ADD INDEX(`obj_module`, `obj_name`, `id_obj`); 

ALTER TABLE `llx_bimpcore_objects_associations` ADD INDEX( `src_object_module`, `src_object_name`, `src_object_type`, `src_id_object`); 
ALTER TABLE `llx_bimpcore_objects_associations` ADD INDEX( `dest_object_module`, `dest_object_name`, `dest_object_type`, `dest_id_object`); 
