SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `llx_actioncomm_resources`;
TRUNCATE TABLE `llx_actioncomm_extrafields`;
TRUNCATE TABLE `llx_actioncomm`;

TRUNCATE TABLE `llx_br_reservation`;

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

TRUNCATE TABLE `llx_propal`;
TRUNCATE TABLE `llx_propal_extrafields`;
TRUNCATE TABLE `llx_propaldet`;
TRUNCATE TABLE `llx_propaldet_extrafields`;

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

TRUNCATE TABLE `llx_product`;
TRUNCATE TABLE `llx_product_copy`;
TRUNCATE TABLE `llx_product_extrafields`;
TRUNCATE TABLE `llx_product_fournisseur_price`;
TRUNCATE TABLE `llx_product_fournisseur_price_extrafields`;
TRUNCATE TABLE `llx_product_fournisseur_price_log`;
TRUNCATE TABLE `llx_product_price`;
TRUNCATE TABLE `llx_product_remise_arriere`;
TRUNCATE TABLE `llx_product_stock`;

TRUNCATE TABLE `llx_societe`;
TRUNCATE TABLE `llx_societe_extrafields`;
TRUNCATE TABLE `llx_societe_account`;
TRUNCATE TABLE `llx_societe_commerciaux`;
TRUNCATE TABLE `llx_societe_extrafields`;
TRUNCATE TABLE `llx_societe_remise`;
TRUNCATE TABLE `llx_societe_remise_except`;
TRUNCATE TABLE `llx_societe_rib`;

TRUNCATE TABLE `llx_socpeople`;
TRUNCATE TABLE `llx_socpeople_extrafields`;

TRUNCATE TABLE `llx_stock_mouvement`;
TRUNCATE TABLE `llx_stock_mouvement_extrafields`;

TRUNCATE TABLE `llx_Synopsis_Histo_User`;

SET FOREIGN_KEY_CHECKS = 1;



--------

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `llx_be_equipment`;
TRUNCATE TABLE `llx_be_equipment_place`;
TRUNCATE TABLE `llx_be_package_product`;

TRUNCATE TABLE `llx_product`;
TRUNCATE TABLE `llx_product_copy`;
TRUNCATE TABLE `llx_product_extrafields`;
TRUNCATE TABLE `llx_product_fournisseur_price`;
TRUNCATE TABLE `llx_product_fournisseur_price_extrafields`;
TRUNCATE TABLE `llx_product_fournisseur_price_log`;
TRUNCATE TABLE `llx_product_price`;
TRUNCATE TABLE `llx_product_remise_arriere`;
TRUNCATE TABLE `llx_product_stock`;

TRUNCATE TABLE `llx_stock_mouvement`;
TRUNCATE TABLE `llx_stock_mouvement_extrafields`;


SET FOREIGN_KEY_CHECKS = 1;