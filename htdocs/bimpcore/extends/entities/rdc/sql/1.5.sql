ALTER TABLE llx_actioncomm
	ADD COLUMN fk_motif_echange TINYINT(1) DEFAULT 0,
	ADD COLUMN action_echange mediumtext DEFAULT NULL,
	ADD COLUMN point_positif_echange VARCHAR(255) DEFAULT NULL,
	ADD COLUMN risque_identifie_echange VARCHAR(255) DEFAULT NULL;
