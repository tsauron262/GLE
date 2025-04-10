ALTER TABLE llx_societe
	ADD COLUMN commentaire_statut_ko VARCHAR(255) DEFAULT NULL,
	ADD COLUMN fk_categorie_maitre int(11) DEFAULT 0,
	ADD COLUMN potentiel_catalogue int(11) DEFAULT NULL;

ALTER TABLE llx_societe MODIFY COLUMN delai_ouverture int(11) DEFAULT NULL NULL;
UPDATE llx_societe SET delai_ouverture = NULL WHERE delai_ouverture = 0;

UPDATE llx_c_actioncomm SET active=0 WHERE id >= 60;
UPDATE llx_c_actioncomm SET active=0 WHERE id IN (2, 5, 6, 11);
UPDATE llx_c_actioncomm SET `position` = 15 WHERE id = 50;
INSERT INTO llx_c_actioncomm (id,code,libelle,active,`position`,icon,user_notif, maj_dercontact_rdc) VALUES
	(14, 'AC_CHAT','Chat',1,6,'fas_comment-dots',0, 1),
	(15, 'AC_RAPPEL','A rappeler',1,8,'fas_bell', 1, 0);
