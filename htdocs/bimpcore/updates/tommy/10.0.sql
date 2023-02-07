UPDATE llx_buc_list_table_config SET cols = replace(cols, 'facnumber', 'ref');
UPDATE llx_buc_list_table_config SET cols = replace(cols, '"tva"', '"total_tva"') WHERE obj_name IN ('Bimp_Facture', 'Bimp_Commande', 'Bimp_Propal');
UPDATE llx_buc_list_table_config SET cols = replace(cols, '"total"', '"total_ttc"') WHERE obj_name IN ('Bimp_Propal');
UPDATE llx_buc_list_table_config SET cols = replace(cols, '"total"', '"total_ht"') WHERE obj_name IN ('Bimp_Facture');