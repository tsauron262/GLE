UPDATE llx_contratdet SET statut=5, date_cloture=NOW() WHERE date_fin_validite < "2018-12-31 00:00:00" AND statut=4;
