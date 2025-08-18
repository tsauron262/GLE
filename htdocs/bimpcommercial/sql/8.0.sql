ALTER table llx_bimp_propal_line
	ADD COLUMN extradata varchar(255);
ALTER table llx_bimp_commande_line
	ADD COLUMN extradata varchar(255);
ALTER table llx_bs_sav_propal_line
	ADD COLUMN extradata varchar(255);

