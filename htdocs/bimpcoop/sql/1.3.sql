CREATE TABLE `llx_emprunt` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `banque` int(11) DEFAULT NULL,
  `pourcentage_frais` int(11) DEFAULT NULL,
  `id_fourn` int(11) DEFAULT NULL,
  `id_interet` int(11) DEFAULT NULL,
  `id_assurance` int(11) DEFAULT NULL,
  `id_capital` int(11) DEFAULT NULL,
  `id_accessoire` int(11) DEFAULT NULL,
  `fk_paiement` int(11) DEFAULT NULL
  PRIMARY KEY (`id`)
);

CREATE TABLE `lou_ammortissement` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `id_emprunt` int(11) DEFAULT NULL,
	  `id_prelev` int(11) DEFAULT NULL,
	  `Date_ECHEANCE` date DEFAULT NULL,
	  `MONTANT_INTERETS` decimal(10,2) DEFAULT NULL,
	  `MONTANT_ASSURANCE` decimal(10,2) DEFAULT NULL,
	  `MONTANT_ACCESSOIRES` decimal(10,2) DEFAULT NULL,
	  `CAPITAL_AMORTI` decimal(10,2) DEFAULT NULL,
	  `MONTANT_ECHEANCE` decimal(10,2) DEFAULT NULL,
	  `CAPITAL_RESTANT_DU` decimal(10,2) DEFAULT NULL,
	  `ELEMENTS_CAPITALISES` decimal(10,2) DEFAULT NULL,
	  `SOMMES_TOTALES_RESTANT_DUES` decimal(10,2) DEFAULT NULL,
	  `date_paiement_reel` date DEFAULT NULL,
	  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=614 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
