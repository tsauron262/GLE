ALTER TABLE llx_societe
	ADD COLUMN commentaire_statut_ko VARCHAR(255) DEFAULT NULL,
	ADD COLUMN fk_categorie_maitre int(11) DEFAULT 0,
	ADD COLUMN potentiel_catalogue int(11) DEFAULT NULL;

ALTER TABLE llx_societe MODIFY COLUMN delai_ouverture int(11) DEFAULT NULL NULL;
UPDATE llx_societe SET delai_ouverture = NULL WHERE delai_ouverture = 0;

