ALTER TABLE `llx_bimp_propal_line` ADD INDEX(`linked_object_name`, `linked_id_object`);
ALTER TABLE `llx_bs_sav_propal_line` ADD INDEX(`linked_object_name`, `linked_id_object`);
ALTER TABLE `llx_bimp_commande_line` ADD INDEX(`linked_object_name`, `linked_id_object`);
ALTER TABLE `llx_bimp_facture_line` ADD INDEX(`linked_object_name`, `linked_id_object`);
ALTER TABLE `llx_bimp_commande_fourn_line` ADD INDEX(`linked_object_name`, `linked_id_object`);
ALTER TABLE `llx_bimp_facture_fourn_line` ADD INDEX(`linked_object_name`, `linked_id_object`);