# ChangeLogs BIMPNOTESFRAIS
*Alexis*

* BASE DE DONNEES
Création de la table : llx_bnf_frais_kilometers *Version 10.2*
	- id INT
	- id_frais INT
	- chevaux INT
	- carburant INT
	- kilometers FLOAT

Ajout dans la table : llx_bnf_frais_montant *version 10.2*
	- periode
	- type

* BNF_Frais.class.php
Création des deux fonction canEdit, canDelete

* BNF_FraisMontant.class.php
Creation du tableau static des periodes restauration (Midi et soir)