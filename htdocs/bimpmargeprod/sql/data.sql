-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1:3306
-- Généré le :  Mar 12 Mars 2019 à 11:34
-- Version du serveur :  5.7.25-0ubuntu0.16.04.2-log
-- Version de PHP :  7.0.33-0ubuntu0.16.04.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données :  `ERP_TEST_TEST8`
--

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_calc_montant`
--

CREATE TABLE `llx_bmp_calc_montant` (
  `id` int(11) NOT NULL,
  `label` varchar(256) NOT NULL,
  `type_source` int(11) NOT NULL DEFAULT '1',
  `id_montant_source` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_total_source` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `source_amount` float NOT NULL DEFAULT '0',
  `id_target` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `percent` float NOT NULL DEFAULT '0',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `required` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_calc_montant_type_montant`
--

CREATE TABLE `llx_bmp_calc_montant_type_montant` (
  `id_calc_montant` int(10) UNSIGNED NOT NULL,
  `id_type_montant` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_categorie_montant`
--

CREATE TABLE `llx_bmp_categorie_montant` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `color` varchar(128) NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event`
--

-- CREATE TABLE `llx_bmp_event` (
--   `id` int(10) UNSIGNED NOT NULL,
--   `name` varchar(128) NOT NULL,
--   `date` datetime DEFAULT NULL,
--   `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
--   `place` int(10) UNSIGNED NOT NULL DEFAULT '0',
--   `status` int(10) UNSIGNED NOT NULL DEFAULT '0',
--   `analytics` varchar(128) NOT NULL DEFAULT '',
--   `ca_moyen_bar` float NOT NULL DEFAULT '0',
--   `tva_billets` int(11) NOT NULL DEFAULT '1',
--   `frais_billet` float NOT NULL DEFAULT '0.2',
--   `default_dl_dist` decimal(24,2) NOT NULL DEFAULT '0.00',
--   `default_dl_prod` decimal(24,2) NOT NULL DEFAULT '0.00',
--   `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
--   `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
--   `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   `bar_20_save` float NOT NULL DEFAULT '0',
--   `bar_55_save` float NOT NULL DEFAULT '0',
--   `billets_loc` int(10) UNSIGNED NOT NULL DEFAULT '0'
-- ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `llx_bmp_event` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `date` datetime DEFAULT NULL,
  `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `place` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `analytics` varchar(128) NOT NULL DEFAULT '',
  `ca_moyen_bar` float NOT NULL DEFAULT '0',
  `tva_billets` int(11) NOT NULL DEFAULT '1',
  `frais_billet` float NOT NULL DEFAULT '0.2',
  `default_dl_dist` decimal(24,2) NOT NULL DEFAULT '0.00',
  `default_dl_prod` decimal(24,2) NOT NULL DEFAULT '0.00',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime ,
  `user_update` int(10) UNSIGNED,
  `date_update` datetime,
  `bar_20_save` float NOT NULL DEFAULT '0',
  `bar_55_save` float NOT NULL DEFAULT '0',
  `billets_loc` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_billets`
--

CREATE TABLE `llx_bmp_event_billets` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_soc_seller` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `seller_name` varchar(256) NOT NULL DEFAULT '',
  `id_tarif` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `quantity` int(11) NOT NULL DEFAULT '0',
  `dl_dist` decimal(24,2) NOT NULL DEFAULT '0.00',
  `dl_prod` decimal(24,2) NOT NULL DEFAULT '0.00',
  `id_coprod` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_calc_montant`
--

CREATE TABLE `llx_bmp_event_calc_montant` (
  `id` int(11) NOT NULL,
  `id_event` int(11) NOT NULL,
  `id_calc_montant` int(11) NOT NULL,
  `percent` float NOT NULL,
  `source_amount` float NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_coprod`
--

CREATE TABLE `llx_bmp_event_coprod` (
  `id` int(11) NOT NULL,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_soc` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `default_part` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_coprod_def_part`
--

CREATE TABLE `llx_bmp_event_coprod_def_part` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_category_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_event_coprod` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `part` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_coprod_part`
--

CREATE TABLE `llx_bmp_event_coprod_part` (
  `id_event_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_coprod` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `part` float UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_group`
--

CREATE TABLE `llx_bmp_event_group` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(256) NOT NULL,
  `number` int(11) NOT NULL DEFAULT '0',
  `rank` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_montant`
--

CREATE TABLE `llx_bmp_event_montant` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_category_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `amount` float NOT NULL DEFAULT '0',
  `tva_tx` float NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `type` int(11) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `id_coprod` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `paiements` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_montant_detail`
--

CREATE TABLE `llx_bmp_event_montant_detail` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_event_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `label` varchar(256) NOT NULL DEFAULT '',
  `quantity` int(11) NOT NULL DEFAULT '1',
  `unit_price` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_event_tarif`
--

CREATE TABLE `llx_bmp_event_tarif` (
  `id` int(11) NOT NULL,
  `id_event` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `amount` float NOT NULL DEFAULT '0',
  `previsionnel` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `droits_loc` float NOT NULL DEFAULT '0',
  `droits_loc_coprods` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_montant_detail_value`
--

CREATE TABLE `llx_bmp_montant_detail_value` (
  `id` int(11) NOT NULL,
  `id_type_montant` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `label` varchar(256) NOT NULL,
  `use_groupe_number` tinyint(1) NOT NULL DEFAULT '0',
  `unit_price` float NOT NULL DEFAULT '0',
  `qty` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_total_inter`
--

CREATE TABLE `llx_bmp_total_inter` (
  `id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL DEFAULT '',
  `all_frais` tinyint(1) NOT NULL DEFAULT '0',
  `all_recettes` tinyint(1) NOT NULL DEFAULT '0',
  `display` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_type_montant`
--

CREATE TABLE `llx_bmp_type_montant` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `id_category` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `id_taxe` int(11) NOT NULL DEFAULT '11',
  `code_compta` varchar(128) NOT NULL DEFAULT '',
  `has_details` tinyint(1) NOT NULL DEFAULT '0',
  `coprod` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bmp_vendeur`
--

CREATE TABLE `llx_bmp_vendeur` (
  `id` int(11) NOT NULL,
  `id_soc` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `label` varchar(256) NOT NULL DEFAULT '',
  `tarifs` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `llx_bmp_calc_montant`
--
ALTER TABLE `llx_bmp_calc_montant`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_categorie_montant`
--
ALTER TABLE `llx_bmp_categorie_montant`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event`
--
ALTER TABLE `llx_bmp_event`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event_billets`
--
ALTER TABLE `llx_bmp_event_billets`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event_calc_montant`
--
ALTER TABLE `llx_bmp_event_calc_montant`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event_coprod`
--
ALTER TABLE `llx_bmp_event_coprod`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event_coprod_def_part`
--
ALTER TABLE `llx_bmp_event_coprod_def_part`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event_coprod_part`
--
ALTER TABLE `llx_bmp_event_coprod_part`
  ADD UNIQUE KEY `id_event_montant` (`id_event_montant`,`id_coprod`);

--
-- Index pour la table `llx_bmp_event_group`
--
ALTER TABLE `llx_bmp_event_group`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event_montant`
--
ALTER TABLE `llx_bmp_event_montant`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event_montant_detail`
--
ALTER TABLE `llx_bmp_event_montant_detail`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_event_tarif`
--
ALTER TABLE `llx_bmp_event_tarif`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_montant_detail_value`
--
ALTER TABLE `llx_bmp_montant_detail_value`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_total_inter`
--
ALTER TABLE `llx_bmp_total_inter`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_type_montant`
--
ALTER TABLE `llx_bmp_type_montant`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bmp_vendeur`
--
ALTER TABLE `llx_bmp_vendeur`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `llx_bmp_calc_montant`
--
ALTER TABLE `llx_bmp_calc_montant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT pour la table `llx_bmp_categorie_montant`
--
ALTER TABLE `llx_bmp_categorie_montant`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event`
--
ALTER TABLE `llx_bmp_event`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event_billets`
--
ALTER TABLE `llx_bmp_event_billets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event_calc_montant`
--
ALTER TABLE `llx_bmp_event_calc_montant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event_coprod`
--
ALTER TABLE `llx_bmp_event_coprod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event_coprod_def_part`
--
ALTER TABLE `llx_bmp_event_coprod_def_part`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event_group`
--
ALTER TABLE `llx_bmp_event_group`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event_montant`
--
ALTER TABLE `llx_bmp_event_montant`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=445;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event_montant_detail`
--
ALTER TABLE `llx_bmp_event_montant_detail`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;
--
-- AUTO_INCREMENT pour la table `llx_bmp_event_tarif`
--
ALTER TABLE `llx_bmp_event_tarif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;
--
-- AUTO_INCREMENT pour la table `llx_bmp_montant_detail_value`
--
ALTER TABLE `llx_bmp_montant_detail_value`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT pour la table `llx_bmp_total_inter`
--
ALTER TABLE `llx_bmp_total_inter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT pour la table `llx_bmp_type_montant`
--
ALTER TABLE `llx_bmp_type_montant`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;
--
-- AUTO_INCREMENT pour la table `llx_bmp_vendeur`
--
ALTER TABLE `llx_bmp_vendeur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;



















-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1:3306
-- Généré le :  Mar 12 Mars 2019 à 11:41
-- Version du serveur :  5.7.25-0ubuntu0.16.04.2-log
-- Version de PHP :  7.0.33-0ubuntu0.16.04.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données :  `ERP_TEST_TEST8`
--

--
-- Contenu de la table `llx_bmp_calc_montant`
--

INSERT INTO `llx_bmp_calc_montant` (`id`, `label`, `type_source`, `id_montant_source`, `id_total_source`, `source_amount`, `id_target`, `percent`, `editable`, `required`, `active`) VALUES
(1, 'CNV', 2, 21, 6, 0, 5, 3.5, 1, 1, 1),
(2, 'SACEM Billeterie', 2, 21, 8, 0, 26, 7.744, 1, 1, 1),
(3, 'Approvisionnement bar', 2, 22, 3, 0, 24, 50, 1, 1, 1),
(7, 'SACEM Bar', 2, 27, 3, 0, 3, 3.37, 1, 1, 1),
(9, 'Sécurité sociale SACEM', 2, 26, 9, 0, 29, 1.1, 1, 1, 1),
(14, 'Ménage Club', 3, 0, 0, 150, 12, 100, 1, 0, 1),
(16, 'SACEM Artistique', 2, 47, 7, 0, 62, 7.744, 1, 0, 1),
(17, 'Promo Locale', 2, 52, 8, 0, 32, 3.5, 1, 0, 1);

--
-- Contenu de la table `llx_bmp_calc_montant_type_montant`
--

INSERT INTO `llx_bmp_calc_montant_type_montant` (`id_calc_montant`, `id_type_montant`) VALUES
(1, 21),
(1, 52),
(2, 21),
(2, 52),
(3, 23),
(3, 22),
(7, 23),
(7, 22),
(9, 62),
(9, 26),
(16, 48),
(16, 47),
(16, 67),
(16, 54),
(17, 21),
(17, 52);

--
-- Contenu de la table `llx_bmp_categorie_montant`
--

INSERT INTO `llx_bmp_categorie_montant` (`id`, `name`, `color`, `position`) VALUES
(1, 'Taxes', 'FFBF00', 12),
(10, 'Accueil', '8A2908', 13),
(11, 'Salle', '8A0886', 14),
(12, 'Coproduction', 'FF6E00', 16),
(13, 'Personnel Technique', '8000FF', 15),
(14, 'Billetterie', 'FF0000', 11),
(15, 'Bar', '04B4AE', 3),
(16, 'Promo', '009C27', 10),
(17, 'Subvention / Mecenat', '009C27', 9),
(18, 'Captation', '4b4b4b', 8),
(20, 'Technique', 'AEB404', 7),
(21, 'Artistique / Engagement', '0404B4', 6),
(22, 'Artistique / Cession', '0404B4', 5),
(23, 'Divers', '4b4b4b', 4),
(24, 'Partenariats', '4b4b4b', 2),
(25, 'Personnel Sécurité', '8000FF', 1);

--
-- Contenu de la table `llx_bmp_montant_detail_value`
--

INSERT INTO `llx_bmp_montant_detail_value` (`id`, `id_type_montant`, `label`, `use_groupe_number`, `unit_price`, `qty`) VALUES
(2, 20, 'Tech1', 0, 300, 0),
(3, 9, 'Repas 15€', 1, 15, 0),
(4, 20, 'ROAD', 0, 260, 0),
(5, 20, 'Régisseur fil', 0, 450, 0),
(6, 4, 'SECURITE', 0, 115, 0),
(7, 4, 'SECURITE Siap 1', 0, 118, 0),
(8, 4, 'SECURITE Siap 2', 0, 121, 0),
(9, 20, 'SON FACE', 0, 330, 0),
(14, 8, 'Twin', 1, 79.5, 0),
(16, 8, 'Single', 1, 57.75, 0),
(17, 20, 'Lumière', 0, 330, 0),
(19, 20, 'SON retours', 0, 330, 0),
(20, 20, 'Plateau', 0, 330, 0),
(21, 9, 'repas 8€', 1, 8, 0),
(22, 9, 'repas 10€', 1, 10, 0),
(23, 9, 'repas 12€', 1, 12, 0),
(24, 8, 'Terminus Single', 1, 52.86, 0),
(26, 9, 'Bénévoles', 0, 8, 20);

--
-- Contenu de la table `llx_bmp_total_inter`
--

INSERT INTO `llx_bmp_total_inter` (`id`, `name`, `all_frais`, `all_recettes`, `display`) VALUES
(3, 'Recettes bar', 0, 0, 1),
(4, 'SACEM', 0, 0, 1),
(5, 'Total recettes hors bar et billetterie', 0, 1, 1),
(6, 'Vente de billets', 0, 0, 1),
(7, 'Frais Artistique', 0, 0, 1),
(8, 'Recette billetterie', 0, 0, 1),
(9, 'SACEM hors bar', 0, 0, 1);

--
-- Contenu de la table `llx_bmp_type_montant`
--

INSERT INTO `llx_bmp_type_montant` (`id`, `name`, `id_category`, `type`, `required`, `editable`, `id_taxe`, `code_compta`, `has_details`, `coprod`) VALUES
(3, 'SACEM Bar', 1, 1, 1, 0, 2462, '65101000', 0, 0),
(4, 'Securité', 25, 1, 1, 0, 2462, '61110000', 1, 1),
(5, 'CNV 3.5% Billetterie', 1, 1, 1, 0, 2462, '65100000', 0, 0),
(8, 'Hôtel', 10, 1, 1, 0, 2462, '', 1, 1),
(9, 'Catering 10%', 10, 1, 1, 0, 2462, '', 1, 1),
(10, 'Repas Exerieur', 10, 1, 1, 0, 2462, '', 1, 1),
(11, 'Location salle', 11, 2, 1, 1, 2462, '', 0, 1),
(12, 'Nettoyage', 11, 1, 1, 1, 2462, '', 0, 1),
(19, 'Regisseur fil', 13, 1, 1, 1, 15, '', 0, 1),
(20, 'Techniciens', 13, 1, 1, 0, 15, '', 1, 1),
(21, 'Billetterie 2,1%', 14, 2, 1, 0, 16, '70620000', 0, 1),
(22, 'Bar 20', 15, 2, 1, 1, 2462, '70700000', 0, 0),
(23, 'Bar 10', 15, 2, 1, 1, 17, '70710000', 0, 0),
(24, 'Achat Bar', 15, 1, 1, 0, 2462, '60710000', 0, 0),
(25, 'Secourismes', 13, 1, 0, 0, 15, '61120000', 1, 1),
(26, 'SACEM Billetterie', 1, 1, 1, 0, 2462, '65101000', 0, 0),
(29, 'Sécurité sociale SACEM', 1, 1, 1, 0, 2462, '65101000', 0, 0),
(31, 'Mise à disposition', 11, 2, 1, 1, 2462, '', 0, 0),
(32, 'Promo Locale', 14, 1, 1, 0, 2462, '', 0, 1),
(33, 'Subvention', 17, 2, 0, 1, 2462, '', 0, 1),
(34, 'Mécénat', 17, 2, 0, 1, 2462, '', 0, 1),
(35, 'Résidence', 17, 2, 0, 1, 2462, '', 0, 0),
(36, 'Droits de captation', 18, 2, 0, 1, 2462, '', 0, 0),
(37, 'Frais de captation', 18, 1, 0, 1, 2462, '', 0, 0),
(38, 'Transport', 23, 1, 0, 0, 2462, '', 1, 1),
(40, 'Facturation divers', 23, 2, 0, 0, 2462, '', 1, 1),
(41, 'Frais d’édition de billetterie', 14, 1, 1, 0, 2462, '', 0, 1),
(42, 'Sonorisation', 20, 1, 0, 0, 2462, '', 1, 1),
(43, 'Lumières', 20, 1, 0, 0, 2462, '', 1, 1),
(44, 'Vidéo', 20, 1, 0, 0, 2462, '', 1, 1),
(45, 'Loc véhicule', 20, 1, 0, 0, 2462, '', 1, 1),
(46, 'Backline', 20, 1, 0, 0, 2462, '', 1, 1),
(47, 'Groupe Cession', 22, 1, 0, 0, 14, '', 1, 1),
(48, 'Support', 22, 1, 0, 0, 14, '', 1, 1),
(49, 'Location salle', 11, 1, 0, 1, 15, '', 0, 1),
(50, 'Communication', 16, 1, 1, 0, 2462, '', 1, 1),
(52, 'Billetterie 5,5%', 14, 2, 1, 1, 14, '70620000', 0, 1),
(54, 'Groupe Engagement', 21, 1, 0, 0, 15, '', 1, 1),
(55, 'Catering et Loges', 10, 1, 0, 1, 2462, '', 0, 1),
(56, 'Cadeaux', 23, 1, 0, 1, 2462, '', 0, 1),
(57, 'Droits de location producteur', 14, 2, 1, 0, 16, '', 1, 1),
(59, 'Fluides', 11, 1, 1, 1, 2462, '', 0, 1),
(60, 'Partenariat', 24, 2, 0, 0, 2462, '', 1, 1),
(62, 'SACEM Groupe', 1, 1, 1, 0, 2462, '65101000', 0, 0),
(63, 'Divers Technique', 20, 1, 0, 0, 2462, '', 1, 1),
(67, 'Artistique', 21, 1, 0, 1, 2462, '', 0, 1),
(68, 'Coréa', 23, 1, 0, 1, 2462, '', 0, 1),
(69, 'SUBV CNV (aide à la diffusion)', 17, 1, 0, 1, 2462, '', 0, 1),
(70, 'SUBV CNV (aide aux résidences)', 17, 1, 0, 1, 2462, '', 0, 1),
(71, 'SUBV CNV (droit de tirage)', 17, 1, 0, 1, 2462, '', 0, 1),
(72, 'SUBV CONSEIL GENERAL 42 - PROJET', 25, 1, 0, 1, 2462, '', 0, 1),
(73, 'SUBV DRAC (EDUCATION ARTISTIQUE)', 17, 1, 0, 1, 2462, '', 0, 1),
(74, 'SUBV SACEM', 25, 1, 0, 1, 2462, '', 0, 1),
(75, 'SUBV CONSEIL REGIONAL PROJET', 17, 1, 0, 1, 2462, '', 0, 1);

--
-- Contenu de la table `llx_bmp_vendeur`
--

INSERT INTO `llx_bmp_vendeur` (`id`, `id_soc`, `label`, `tarifs`, `active`) VALUES
(2, 0, 'Fnac', 'TARIF NORMAL,TARIF REDUIT,FILGOOD', 1),
(3, 0, 'Ticketnet', 'TARIF NORMAL,TARIF REDUIT,FILGOOD', 1),
(4, 0, 'Digitick', 'TARIF NORMAL,TARIF REDUIT', 1),
(5, 0, 'Prévente Le Fil', 'TARIF NORMAL,TARIF REDUIT,FILGOOD', 1),
(6, 0, 'Guichet', 'GUICHET NORMAL,GUICHET REDUIT', 1),
(7, 0, 'Web Fil', 'TARIF NORMAL,TARIF REDUIT,FILGOOD', 1);
