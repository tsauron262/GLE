--
-- Base de données :  `GLE_TEST_BIMP6data`
--

-- --------------------------------------------------------

--
-- Structure de la table `llx_bf_comment`
--

CREATE TABLE `llx_bf_comment` (
  `id` int(11) NOT NULL,
  `id_demande` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `user_create` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bf_demande`
--

CREATE TABLE `llx_bf_demande` (
  `id` int(11) NOT NULL,
  `duration` int(10) UNSIGNED NOT NULL,
  `periodicity` int(10) UNSIGNED NOT NULL,
  `vr` int(11) NOT NULL,
  `insurance` tinyint(1) NOT NULL DEFAULT '0',
  `annexe` int(11) NOT NULL DEFAULT '0',
  `ca_prevu` float NOT NULL DEFAULT '0',
  `pba_prevu` float NOT NULL DEFAULT '0',
  `date_livraison` date DEFAULT NULL,
  `date_loyer` date DEFAULT NULL,
  `id_supplier` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_supplier_contact` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bf_refinanceur`
--

CREATE TABLE `llx_bf_refinanceur` (
  `id` int(11) NOT NULL,
  `id_demande` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bf_rent`
--

CREATE TABLE `llx_bf_rent` (
  `id` int(11) NOT NULL,
  `id_demande` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `amount_ht` float NOT NULL DEFAULT '0',
  `payment` int(11) NOT NULL DEFAULT '0',
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bh_equipment`
--

CREATE TABLE `llx_bh_equipment` (
  `id` int(11) NOT NULL,
  `id_product` int(10) UNSIGNED NOT NULL,
  `serial` varchar(128) NOT NULL,
  `date_purchase` date DEFAULT NULL,
  `date_warranty_end` date DEFAULT NULL,
  `warranty_type` varchar(128) NOT NULL DEFAULT '0',
  `admin_login` varchar(128) DEFAULT NULL,
  `admin_pword` varchar(128) DEFAULT NULL,
  `note` text NOT NULL,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bh_equipment_contrat`
--

CREATE TABLE `llx_bh_equipment_contrat` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_object` int(10) UNSIGNED NOT NULL,
  `id_associate` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bh_inter`
--

CREATE TABLE `llx_bh_inter` (
  `id` int(11) NOT NULL,
  `id_ticket` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tech_id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timer` int(10) UNSIGNED NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '1',
  `status` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bh_note`
--

CREATE TABLE `llx_bh_note` (
  `id` int(11) NOT NULL,
  `id_ticket` int(10) UNSIGNED NOT NULL,
  `id_inter` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `visibility` int(11) NOT NULL,
  `content` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED DEFAULT NULL,
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bh_ticket`
--

CREATE TABLE `llx_bh_ticket` (
  `id` int(11) NOT NULL,
  `id_contrat` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ticket_number` varchar(128) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bimp_timer`
--

CREATE TABLE `llx_bimp_timer` (
  `id` int(11) NOT NULL,
  `obj_module` varchar(128) NOT NULL,
  `obj_name` varchar(128) NOT NULL,
  `id_obj` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(128) NOT NULL,
  `time_session` int(11) NOT NULL,
  `session_start` int(11) DEFAULT NULL,
  `id_user` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `llx_bf_comment`
--
ALTER TABLE `llx_bf_comment`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bf_demande`
--
ALTER TABLE `llx_bf_demande`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bf_refinanceur`
--
ALTER TABLE `llx_bf_refinanceur`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bf_rent`
--
ALTER TABLE `llx_bf_rent`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bh_equipment`
--
ALTER TABLE `llx_bh_equipment`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bh_equipment_contrat`
--
ALTER TABLE `llx_bh_equipment_contrat`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bh_inter`
--
ALTER TABLE `llx_bh_inter`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bh_note`
--
ALTER TABLE `llx_bh_note`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bh_ticket`
--
ALTER TABLE `llx_bh_ticket`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bimp_timer`
--
ALTER TABLE `llx_bimp_timer`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `llx_bf_comment`
--
ALTER TABLE `llx_bf_comment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT pour la table `llx_bf_demande`
--
ALTER TABLE `llx_bf_demande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pour la table `llx_bf_refinanceur`
--
ALTER TABLE `llx_bf_refinanceur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pour la table `llx_bf_rent`
--
ALTER TABLE `llx_bf_rent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pour la table `llx_bh_equipment`
--
ALTER TABLE `llx_bh_equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT pour la table `llx_bh_equipment_contrat`
--
ALTER TABLE `llx_bh_equipment_contrat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pour la table `llx_bh_inter`
--
ALTER TABLE `llx_bh_inter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT pour la table `llx_bh_note`
--
ALTER TABLE `llx_bh_note`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `llx_bh_ticket`
--
ALTER TABLE `llx_bh_ticket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT pour la table `llx_bimp_timer`
--
ALTER TABLE `llx_bimp_timer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
