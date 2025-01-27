ALTER TABLE llx_bs_centre_sav
	ADD token VARCHAR(255) NOT NULL default '',
	ADD id_group int(11) NOT NULL default 0,
	ADD warning text NOT NULL DEFAULT '';
