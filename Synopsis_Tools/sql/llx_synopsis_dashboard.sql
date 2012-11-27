-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Serveur: 127.0.0.1
-- Généré le : Mar 27 Mars 2012 à 17:56
-- Version du serveur: 5.1.33
-- Version de PHP: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Base de données: `gle`
--

-- --------------------------------------------------------

--
-- Structure de la table `llx_Synopsis_Dashboard`
--

CREATE TABLE IF NOT EXISTS `llx_Synopsis_Dashboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `params` longtext,
  `user_refid` int(11) DEFAULT NULL,
  `dash_type_refid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_refid` (`user_refid`),
  KEY `dash_type_refid` (`dash_type_refid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_Synopsis_Dashboard_module`
--

CREATE TABLE IF NOT EXISTS `llx_Synopsis_Dashboard_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_refid` int(11) DEFAULT NULL,
  `type_refid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `module_refid` (`module_refid`),
  KEY `type_refid` (`type_refid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_Synopsis_Dashboard_page`
--

CREATE TABLE IF NOT EXISTS `llx_Synopsis_Dashboard_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(50) DEFAULT NULL,
  `parent` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Structure de la table `llx_Synopsis_Dashboard_settings`
--

CREATE TABLE IF NOT EXISTS `llx_Synopsis_Dashboard_settings` (
  `module_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `value` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_Synopsis_Dashboard_widget`
--

CREATE TABLE IF NOT EXISTS `llx_Synopsis_Dashboard_widget` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_index` (`module`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `llx_Synopsis_Dashboard`
--
-- ALTER TABLE `llx_Synopsis_Dashboard`
--   ADD CONSTRAINT `llx_Synopsis_Dashboard_ibfk_1` FOREIGN KEY (`user_refid`) REFERENCES `llx_user` (`rowid`) ON DELETE CASCADE,
--   ADD CONSTRAINT `llx_Synopsis_Dashboard_ibfk_2` FOREIGN KEY (`dash_type_refid`) REFERENCES `llx_Synopsis_Dashboard_page` (`id`) ON DELETE CASCADE;
-- 
-- --
-- -- Contraintes pour la table `llx_Synopsis_Dashboard_module`
-- --
-- ALTER TABLE `llx_Synopsis_Dashboard_module`
--   ADD CONSTRAINT `llx_Synopsis_Dashboard_module_ibfk_1` FOREIGN KEY (`type_refid`) REFERENCES `llx_Synopsis_Dashboard_page` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `llx_Synopsis_Dashboard_module_ibfk_2` FOREIGN KEY (`module_refid`) REFERENCES `llx_Synopsis_Dashboard_widget` (`id`) ON DELETE CASCADE;







INSERT INTO `llx_Synopsis_Dashboard_page` (`id`, `page`, `parent`) VALUES
(1, 'GMAO-SAV', 2),
(2, 'GMAO', NULL),
(3, 'Ventes', NULL),
(4, 'Accueil', NULL),
(5, 'Gestion', NULL),
(6, 'Produit/Services', NULL),
(7, 'Achat', NULL),
(8, 'Tech', NULL),
(9, 'Tiers', NULL),
(10, 'Produits', 6),
(11, 'Services', 6),
(12, 'Stock', 6),
(13, 'Catégorie', 6),
(14, 'Facture', 7),
(15, 'Commande', 7),
(16, 'Prospect', 3),
(17, 'Client', 3),
(18, 'Contacts', 3),
(19, 'Action Co.', 3),
(20, 'Propal', 3),
(21, 'Commandes', 3),
(22, 'Expéditions', 3),
(23, 'Contrat', 3),
(24, 'Interventions', 3),
(25, 'Calendrier', 3),
(26, 'Fourn', 5),
(27, 'Client', 5),
(28, 'Cessionnaire', 5),
(29, 'Remise Chèque', 5),
(30, 'propale', 5),
(31, 'Commande', 5),
(32, 'Taxes', 5),
(33, 'Ventilation', 5),
(34, 'Prélèvement', 5),
(35, 'Banques', 5),
(36, 'Projet', NULL),
(37, 'Tache', 36),
(38, 'Activité', 36),
(39, 'Outils', NULL),
(40, 'Interventions', 8),
(41, 'Affaire', NULL),
(42, 'GMAO-Retour', 2),
(43, 'Prepa.', NULL),
(44, 'Groupe Com.', 3),
(45, 'Process', NULL),
(46, 'Chrono', NULL);






INSERT INTO `llx_Synopsis_Dashboard_widget` (`id`, `nom`, `module`, `active`) VALUES
(4, 'SAV - En attente de traitement', 'listSAVAttenteTraitement', 1),
(8, 'SAV - Nouvelle demande', 'creerSAV', 1),
(9, 'SAV - En r&eacute;paration', 'listSAVAttenteReparation', 1),
(10, 'SAV - En attente du client', 'listSAVAttenteClient', 1),
(12, 'GMAO - Liens', 'liensGMAO', 1),
(13, 'GMAO - Tickets non pris', 'listTicketAttente', 1),
(14, 'Propositions commerciales - Recherche', 'recherchePropale', 1),
(15, 'Propositions commerciales - 10 dernier&egrave;s brouillons', 'listPropaleBrouillon', 1),
(16, 'Contrat - Recherche', 'rechercheContrat', 1),
(17, 'Commande client - 10 derniers brouillons', 'listCommandeBrouillon', 1),
(18, 'Action com. - 10 derni&egrave;res actions', 'listActionTODO', 1),
(19, 'Action com. - 10 derni&egrave;res effectu&eacute;es', 'listActionDone', 1),
(20, 'Tiers - 5 derniers clients/prospects', 'listClientProspect', 1),
(21, 'Propositions commerciales - 10 dernier&egrave;s prop. ouvertes', 'listPropaleOuverte', 1),
(22, 'Propositions commerciales - 10 dernier&egrave;s prop. ferm&eacute;es', 'listPropaleFerme', 1),
(23, 'Propositions commerciales - 10 dernier&egrave;s prop. &agrave; valider', 'listPropaleAValider', 1),
(24, 'Facture - Recherche', 'rechercheFacture', 1),
(25, 'Facture - 10 derniers brouillons', 'listFactureBrouillon', 1),
(26, 'Facture fournisseur - 10 derniers brouillons', 'listFactureFournBrouillon', 1),
(27, 'Charges sociales - A payer', 'listChargeAPayer', 1),
(28, 'Tiers - 5 derniers clients', 'listClient', 1),
(29, 'Tiers - 5 derniers fournisseurs', 'listFourn', 1),
(30, 'Commande client - 10 derniers com. &agrave; facturer', 'listCommandeAFacturer', 1),
(31, 'Facture - 10 derniers impay&eacute;s', 'listFactureImpayee', 1),
(32, 'Facture fournisseur - 10 derni&egrave;res impay&eacute;s', 'listFactureFournImpayee', 1),
(33, 'Fournisseur - Catégories', 'categorieFourn', 1),
(34, 'Fournisseur - Stat. commandes', 'statCommandeFourn', 1),
(35, 'Commande fournisseur - 10 derniers brouillons', 'listCommandeFournBrouillon', 1),
(36, 'Produits/Services - Recherche', 'rechercheProduit', 1),
(37, 'Produits/Services - Statistiques', 'statProduitService', 1),
(38, 'Produits - 10 derniers ajout&eacute;s', 'listProduit', 1),
(49, 'Fournisseurs - Les 10 derni&amp;egrave;res factures impay&amp;eacute;es', 'boxFactureFournImp', 1),
(50, 'Propositions commerciales - Les 10 derni&amp;egrave;res prop. enregistr&amp;eacute;s', 'boxDernierePropale', 1);


INSERT INTO `llx_Synopsis_Dashboard_widget` (`id`, `nom`, `module`, `active`) VALUES
(51, 'Votre tableau de board - Aide', 'hello_world', 1),
(52, 'Zimbra - RSS', 'boxZimbra', 0),
(55, 'Hello Bevan', 'hello_bevan', 0),
(58, 'Contrat - Derniers produits/services contract&amp;eacute;s', 'boxServicesVendus', 1),
(59, 'Tiers - Derniers prospects', 'boxProspect', 1),
(60, 'Facture - Derni&amp;egrave;res factures clients', 'boxFacture', 1),
(61, 'Tiers - Derniers fournisseurs', 'boxFournisseurs', 1),
(62, 'Produits/Services - Derniers produits/services', 'boxProduits', 1),
(63, 'Action com. - Derni&amp;egrave;res actions', 'boxAction', 1),
(65, 'Tiers - Derniers clients', 'boxClient', 1),
(66, 'Commande client - Derni&amp;egrave;res commandes', 'boxCommande', 1),
(67, 'Banque - Soldes Comptes courants', 'boxCompte', 1),
(68, 'DI - Derni&amp;egrave;res demandes d&amp;#039;intervention', 'boxDemandeInterv', 1),
(69, 'NDF - Informations sur les derniers d&amp;eacute;placements', 'boxDeplacement', 1),
(70, 'Facture - Derni&amp;egrave;res factures fournisseurs', 'boxFactureFourn', 1),
(72, 'Facture - Plus anciennes factures clients impay&amp;eacute;es', 'boxFactureImp', 1),
(73, 'Cat&amp;eacute;gories - Arbre produit', 'treeCategoriesProduits', 1),
(74, 'Commande Fournisseur - Personnes habilit&amp;eacute;es &amp;agrave; approuver les commandes', 'listUserCanApproveComFourn', 1),
(82, 'Produits/Services - 10 derniers ajouts', 'listProduitService', 1),
(83, 'Services - 10 derniers ajouts', 'listService', 1),
(85, 'Produits - Statistiques', 'statProduit', 1),
(86, 'Services - Statistiques', 'statService', 1),
(87, 'Stats - 5 meilleures ventes produits/services', 'statOfcProduits', 1),
(88, 'Stats - Toutes les propositions com. par status', 'statOfcPropalStatusAll', 1),
(92, 'Stats - Mes propositions com. par status', 'statOfcPropalMyStatus', 1),
(93, 'Stats - Toutes les commandes par status', 'statOfcOrderStatusAll', 1),
(94, 'Stats - Solde bancaire', 'statOfcSoldeBanque', 1),
(95, 'Prospects - Statistiques', 'statProspect', 1),
(97, 'Prospects - 10 derniers &amp;agrave; contacter', 'listSocToContact', 1),
(98, 'Liens CRM', 'liensCRM', 1),
(99, 'Commande client - 10 derniers com. &amp;agrave; traiter', 'listCommandeATraiter', 1),
(100, 'Commande client - 10 derniers com. cl&amp;ocirc;tur&amp;eacute;es', 'listCommandeCloture', 1);


INSERT INTO `llx_Synopsis_Dashboard_widget` (`id`, `nom`, `module`, `active`) VALUES
(101, 'Commande client - 10 derniers com. en cours', 'listCommandeEnCours', 1),
(104, 'Exp&amp;eacute;dition - Rechercher', 'rechercheExpedition', 1),
(105, 'Exp&amp;eacute;dition - 5 derni&amp;egrave;res exp&amp;eacute;dition', 'listExpedition', 1),
(106, 'Contrat - 10 derniers brouillons', 'listContratBrouillon', 1),
(107, 'Contrat - L&amp;eacute;gendes', 'contratLegend', 1),
(108, 'Contrat - 10 derniers contrats modifi&amp;eacute;s', 'listContrat', 1),
(109, 'Contrat - 10 derniers services inactifs (parmi les contrats valid&amp;eacute;s)', 'listContratServiceInactif', 1),
(110, 'Contrat - 10 derniers services modifi&amp;eacute;s', 'listContratServiceModifie', 1),
(111, 'Remise de ch&amp;egrave;que - 10 derni&egrave;res remises', 'listBordereauRemiseCheque', 1),
(112, 'Remise de ch&amp;egrave;que - Statistiques', 'statRemiseCheque', 1),
(113, 'Compta - Lignes &amp;agrave; ventiller', 'statAVentiler', 1),
(114, 'Compta - Stats. comptes g&amp;eacute;n&amp;eacute;raux (client)', 'statComptaVentile', 1),
(116, 'Compta - Stats. comptes g&amp;eacute;n&amp;eacute;raux (fourn.)', 'statComptaVentileFourn', 1),
(118, 'Pr&amp;eacute;l&amp;egrave;vement - 10 derniers pr&amp;eacute;l&amp;egrave;vements', 'listPrelevement', 1),
(119, 'Pr&amp;eacute;l&amp;egrave;vement - 10 derni&amp;egrave;re factures en attente', 'listPrelevementFacture', 1),
(120, 'Pr&amp;eacute;l&amp;egrave;vement - Statistiques', 'statPrelevement', 1),
(121, 'Projets - Statistiques', 'statProject', 1),
(122, 'Projets - Statistiques par soci&eacute;t&eacute;', 'statProjectPerSoc', 1),
(131, 'Projets - T&amp;acirc;che en d&amp;eacute;passement', 'statProjectWarning', 1),
(134, 'Projets - Statistiques temps par t&amp;acirc;che', 'statProjectTaskPerDuration', 1),
(135, 'Projets - Statistiques par t&amp;acirc;che', 'statProjectPerTask', 1),
(137, 'Affaire - 10 derni&amp;egrave;res affaires', 'listAffaire', 1),
(138, 'Stats - 5 meilleures ventes produits', 'statOfcProduit', 1),
(139, 'Stats - 5 meilleures ventes de contrat', 'statOfcProduitContrat', 1),
(140, 'Stats - 5 meilleures ventes services', 'statOfcService', 1),
(141, 'Stock - Rechercher', 'rechercheStock', 1),
(142, 'Stock - 10 derniers entrepots', 'listStock', 1),
(143, 'Actions - Rechercher', 'rechercheAction', 1),
(144, 'Liens Actions', 'liensAction', 1),
(145, 'Commande client - Rechercher', 'rechercheCommande', 1),
(146, 'Exp&amp;eacute;dition - 10 derni&amp;egrave;res exp&amp;eacute;ditions &amp;agrave; valider', 'listExpeditionAValider', 1),
(147, 'FI - Les  derni&amp;egrave;res fiches d&amp;#039;intervention', 'boxFicheInterv', 1),
(148, 'Projet - Liste par soci&amp;eacute;t&amp;eacute', 'listProjetSoc', 1),
(149, 'Projet - Liste &amp; avancement', 'listProjet', 1),
(150, 'Projets - Activit&amp;eacute;', 'listProjetActivite', 1);


INSERT INTO `llx_Synopsis_Dashboard_widget` (`id`, `nom`, `module`, `active`) VALUES
(151, 'Stats - Activit&amp;eacute;s pr&amp;eacute;vues', 'statOfcProjetActivitePrevu', 1),
(152, 'Stats - Activit&eacute;s effectu&eacute;es', 'statOfcProjetActiviteEffective', 1),
(153, 'Stats - Mon Activit&amp;eacute;s pr&amp;eacute;vus', 'statOfcProjetMonActivitePrevu', 1),
(154, 'Stats - Mon Activit&amp;eacute;s effectu&amp;eacute;es', 'statOfcProjetMonActiviteEffective', 1),
(155, 'Projets - Mon activit&amp;eacute; journali&amp;egrave;re', 'listProjetMonActiviteDetail', 1),
(156, 'Projets - Mon activit&amp;eacute;', 'listProjetMonActivite', 1),
(158, 'Retards - Liste des retards', 'listRetard', 1),
(159, 'Cat&amp;eacute;gories - Arbre soci&amp;eacute;t&amp;eacute;s', 'treeCategoriesSocietes', 1),
(160, 'Cat&amp;eacute;gories - Arbre fournisseurs', 'treeCategoriesFourn', 1),
(161, 'Tiers - 5 derniers cessionnaires', 'listCess', 1),
(163, 'GA - 10 derniers contrats modifi&amp;eacute;s', 'listContratGA', 1),
(164, 'GA - 10 derni&amp;egrave;res factures', 'listFactureCess', 1),
(165, 'GMAO - 10 derniers contrats SAV modifi&amp;eacute;s', 'listContratSAV', 1),
(166, 'GMAO - 10 prochains contrats SAV &amp;agrave &amp;eacute;ch&amp;eacute;ance', 'listContratSAVParDateFin', 1),
(167, 'Pr&amp;eacute;paration commande - 10 derniers com. non examin&amp;eacute;', 'listPrepaCommande', 1),
(168, 'Pr&amp;eacute;paration commande - 10 derniers com. en cours', 'listPrepaCommandeEnCours', 1),
(169, 'Pr&amp;eacute;paration commande - 10 derniers com. en attente', 'listPrepaCommandeProb', 1),
(170, 'Pr&amp;eacute;paration commande - 10 derniers groupes modifi&amp;eacute;s', 'listPrepaGroupCommande', 1),
(171, 'Contrat - 10 prochains contrats mixtes', 'listContratMixte', 1),
(172, 'Navigation - Derniers &amp;eacute;l&amp;eacute;ments visit&amp;eacute;s', 'NavigationUser', 1),
(173, 'Propositions commerciales - 10 derni&amp;egrave;res prop. de financement', 'listPropaleFinancement', 1),
(174, 'Pr&amp;eacute;paration commande - Exp&amp;eacute;dition - Derni&amp;egrave;res exp&amp;eacute;ditions', 'listExpeditionPrepaCommande', 1),
(175, 'Propositions commerciales - Mes derni&amp;egrave;res prop. de financement', 'listMyPropaleFinancement', 1),
(176, 'Pr&amp;eacute;paration commande - Derniers contrats &amp;agrave; saisir', 'listPrepaCommandeContratAFaire', 1),
(177, 'Chrono - Derniers chrono &amp;agrave; valider', 'listChronoAValider', 1),
(182, 'Chrono - Derniers chronos (Document départ)', 'listChronoModele2', 1);