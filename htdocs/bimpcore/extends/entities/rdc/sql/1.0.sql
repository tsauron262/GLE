CREATE TABLE IF NOT EXISTS
llx_c_societe_rdc_priorite (
	rowid int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	libelle varchar(255) NOT NULL,
	ordre int(11) NOT NULL,
	active tinyint(1) NOT NULL DEFAULT '1'
);
INSERT INTO llx_c_societe_rdc_priorite (libelle, ordre) VALUES ('P0', 10), ('P1', 20), ('P2', 30), ('P3', 40);


CREATE TABLE IF NOT EXISTS
llx_c_societe_rdc_statut (
	rowid int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	libelle varchar(255) NOT NULL,
	ordre int(11) NOT NULL,
	active tinyint(1) NOT NULL DEFAULT '1'
);
INSERT INTO llx_c_societe_rdc_statut (libelle, ordre) VALUES
	('Prospection: demande entrante', 10),
	('Prospection: lead identifié', 20),
	('Prospection: prise de contact', 30),
	('Prospection: contact et présentation ok', 40),
	('Prospect KO', 50),
	('KYC en cours', 60),
	('MANGOPAY en cours', 70),
	('En attente onboarding catalogue', 80),
	('Onboarding catalogue KO', 90),
	('Onboarding catalogue OK', 100),
	('Live', 110),
	('Résilié', 120),
	('Suspendu', 130),
	('Fermé', 140);

CREATE TABLE IF NOT EXISTS
llx_c_societe_rdc_source (
	rowid int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	libelle varchar(255) NOT NULL,
	ordre int(11) NOT NULL,
	active tinyint(1) NOT NULL DEFAULT '1'
);
INSERT INTO llx_c_societe_rdc_source (libelle, ordre) VALUES
	('MIRAKL connect', 10),
	('Interne', 20),
	('Linkedin', 30),
	('Chasse', 40),
	('Octopia', 50),
	('Ancienne boutique', 60),
	('Partenaire', 70),
	('Autre', 80);
