-- Avant
TRUNCATE TABLE `llx_actioncomm_resources`;
TRUNCATE TABLE `llx_actioncomm_extrafields`;
TRUNCATE TABLE `llx_actioncomm`;

TRUNCATE TABLE `llx_br_reservation`;

-- Après
TRUNCATE TABLE `llx_bimp_apple_internal_stock`;
TRUNCATE TABLE `llx_bimp_apple_part_stock_mvt`;
TRUNCATE TABLE `llx_be_equipment`;
TRUNCATE TABLE `llx_be_equipment_place`;
TRUNCATE TABLE `llx_bimp_commande_fourn_line`;
TRUNCATE TABLE `llx_bimp_commande_line`;
TRUNCATE TABLE `llx_bimp_facture_line`;
TRUNCATE TABLE `llx_bimp_product_cur_pa`;
TRUNCATE TABLE `llx_bimp_propal_line`;
TRUNCATE TABLE `llx_bimp_remise_globale`;

TRUNCATE TABLE `llx_element_element`;
TRUNCATE TABLE `llx_element_contact`;

TRUNCATE TABLE `llx_object_line_equipment`;
TRUNCATE TABLE `llx_object_line_remise_arriere`;
TRUNCATE TABLE `llx_object_line_remise`;

TRUNCATE TABLE `llx_commandedet_extrafields`;
TRUNCATE TABLE `llx_commandedet`;
TRUNCATE TABLE `llx_commande_extrafields`;
TRUNCATE TABLE `llx_commande`;

TRUNCATE TABLE `llx_commande_fournisseurdet_extrafields`;
TRUNCATE TABLE `llx_commande_fournisseurdet`;
TRUNCATE TABLE `llx_commande_fournisseur_extrafields`;
TRUNCATE TABLE `llx_commande_fournisseur`;

TRUNCATE TABLE `llx_facturedet_extrafields`;
TRUNCATE TABLE `llx_facturedet`;
TRUNCATE TABLE `llx_facture_extrafields`;
TRUNCATE TABLE `llx_facture`;

TRUNCATE TABLE `llx_bs_sav`;
TRUNCATE TABLE `llx_bs_sav_propal_line`;

TRUNCATE TABLE `llx_facture_fourn_det_extrafields`;
TRUNCATE TABLE `llx_facture_fourn_det`;
TRUNCATE TABLE `llx_facture_fourn_extrafields`;
TRUNCATE TABLE `llx_facture_fourn`;






TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;
TRUNCATE TABLE ``;




TRUNCATE TABLE `llx_bank`;
TRUNCATE TABLE `llx_bank_url`;

TRUNCATE TABLE `llx_bc_caisse_mvt`;
TRUNCATE TABLE `llx_bc_caisse_session`;
TRUNCATE TABLE `llx_bc_paiement`;

TRUNCATE TABLE `llx_be_package_product`;
TRUNCATE TABLE `llx_bimp_gsx_repair`;

TRUNCATE TABLE `llx_bimpcore_log`;
TRUNCATE TABLE `llx_bimpcore_file`;
TRUNCATE TABLE `llx_bimpcore_hashtag`;
TRUNCATE TABLE `llx_bimpcore_history`;
TRUNCATE TABLE `llx_bimpcore_link`;
TRUNCATE TABLE `llx_bimpcore_note`;
TRUNCATE TABLE `llx_bimpcore_object_log`;
TRUNCATE TABLE `llx_bimpcore_objects_associations`;
TRUNCATE TABLE `llx_bimpcore_signature`;
TRUNCATE TABLE `llx_bimpcore_signature_signataire`;
TRUNCATE TABLE `llx_bimpmail`;

TRUNCATE TABLE `llx_bl_commande_fourn_reception`;
TRUNCATE TABLE `llx_bl_commande_shipment`;



TRUNCATE TABLE `llx_bs_apple_part`;
TRUNCATE TABLE `llx_bs_sav_issue`;
TRUNCATE TABLE `llx_bs_sav_pret`;
TRUNCATE TABLE `llx_bs_sav_product`;
TRUNCATE TABLE `llx_bs_sav_propal_line`;





TRUNCATE TABLE `llx_paiement`;
TRUNCATE TABLE `llx_paiement_facture`;

TRUNCATE TABLE `llx_product_fournisseur_price`;
TRUNCATE TABLE `llx_product_fournisseur_price_extrafields`;
TRUNCATE TABLE `llx_product_fournisseur_price_log`;
TRUNCATE TABLE `llx_product_price`;
TRUNCATE TABLE `llx_product_remise_arriere`;
TRUNCATE TABLE `llx_product_stock`;

TRUNCATE TABLE `llx_societe_account`;
TRUNCATE TABLE `llx_societe_commerciaux`;
TRUNCATE TABLE `llx_societe_extrafields`;
TRUNCATE TABLE `llx_societe_remise`;
TRUNCATE TABLE `llx_societe_remise_except`;
TRUNCATE TABLE `llx_societe_rib`;

TRUNCATE TABLE `llx_stock_mouvement`;
TRUNCATE TABLE `llx_stock_mouvement_extrafields`;


INSERT into llx_product (ref,entity,ref_ext,datec,tms,fk_parent,label,description,note,customcode,fk_country,fk_state,price,price_ttc,price_min,price_min_ttc,price_base_type,tva_tx,recuperableonly,localtax1_tx,localtax1_type,localtax2_tx,localtax2_type,fk_user_author,fk_user_modif,tosell,tobuy,onportal,fk_product_type,duration,seuil_stock_alerte,barcode,fk_barcode_type,accountancy_code_sell,accountancy_code_sell_intra,accountancy_code_sell_export,accountancy_code_buy,accountancy_code_buy_intra,accountancy_code_buy_export,partnumber,weight,weight_units,length,length_units,surface,surface_units,volume,volume_units,stock,pmp,cur_pa_ht,cur_pa_origin,cur_pa_id_origin,id_cur_fp,fifo,lifo,canvas,finished,hidden,import_key,desiredstock,url,tobatch,fk_price_expression,fk_unit,cost_price,default_vat_code,price_autogen,note_public,model_pdf,width,width_units,height,height_units,no_fixe_prices,duree,lock_admin,alerteActive,type_remise_arr,fk_project,net_measure,net_measure_units,batch_mask,lifetime,qc_frequency,mandatory_period,fk_default_bom, temp_crt)
SELECT ref,entity,ref_ext,datec,tms,fk_parent,label,description,note,customcode,fk_country,fk_state,price,price_ttc,price_min,price_min_ttc,price_base_type,tva_tx,recuperableonly,localtax1_tx,localtax1_type,localtax2_tx,localtax2_type,fk_user_author,fk_user_modif,tosell,tobuy,onportal,fk_product_type,duration,seuil_stock_alerte,barcode,fk_barcode_type,accountancy_code_sell,accountancy_code_sell_intra,accountancy_code_sell_export,accountancy_code_buy,accountancy_code_buy_intra,accountancy_code_buy_export,partnumber,weight,weight_units,length,length_units,surface,surface_units,volume,volume_units,stock,pmp,cur_pa_ht,cur_pa_origin,cur_pa_id_origin,id_cur_fp,fifo,lifo,canvas,finished,hidden,import_key,desiredstock,url,tobatch,fk_price_expression,fk_unit,cost_price,default_vat_code,price_autogen,note_public,model_pdf,width,width_units,height,height_units,no_fixe_prices,duree,lock_admin,alerteActive,type_remise_arr,fk_project,net_measure,net_measure_units,batch_mask,lifetime,qc_frequency,mandatory_period,fk_default_bom, ra.value FROM ERP_PROD_BIMP.llx_product a left JOIN ERP_PROD_BIMP.llx_product_remise_arriere ra ON ra.id_product = a.rowid AND ra.type = 'CRT' WHERE tosell = 1 AND tobuy = 1;





INSERT INTO `llx_product_remise_arriere` (id_product, type, nom, value, active) SELECT rowid, 'crt', 'CRT', temp_crt, 1 FROM llx_product WHERE temp_crt > 0;