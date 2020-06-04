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
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
