ALTER TABLE llx_societe
	ADD COLUMN fk_statut_kyc int(11) NOT NULL DEFAULT 0
	AFTER fk_statut_rdc;
