

CREATE TABLE `llx_bimpcore_conf` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bimpcore_file`
--

CREATE TABLE `llx_bimpcore_file` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_module` varchar(128) DEFAULT NULL,
  `parent_object_name` varchar(128) NOT NULL,
  `id_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `file_name` varchar(128) NOT NULL,
  `file_ext` varchar(12) DEFAULT NULL,
  `file_size` int(11) NOT NULL,
  `description` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_create` datetime NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bimpcore_history`
--

CREATE TABLE `llx_bimpcore_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `module` varchar(128) NOT NULL,
  `object` varchar(128) NOT NULL,
  `id_object` int(10) UNSIGNED NOT NULL,
  `field` varchar(128) NOT NULL,
  `value` text NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bimpcore_list_config`
--

CREATE TABLE `llx_bimpcore_list_config` (
  `id` int(11) NOT NULL,
  `owner_type` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `id_owner` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `obj_module` varchar(256) NOT NULL DEFAULT '',
  `obj_name` varchar(256) NOT NULL DEFAULT '',
  `list_name` varchar(256) NOT NULL DEFAULT 'default',
  `cols` text NOT NULL,
  `sort_field` varchar(256) NOT NULL DEFAULT 'id',
  `sort_option` varchar(128) NOT NULL DEFAULT '',
  `sort_way` varchar(8) NOT NULL DEFAULT 'asc',
  `nb_items` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bimpcore_list_filters`
--

CREATE TABLE `llx_bimpcore_list_filters` (
  `id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL DEFAULT '',
  `owner_type` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `id_owner` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `obj_module` varchar(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `list_type` varchar(128) NOT NULL DEFAULT '',
  `list_name` varchar(128) NOT NULL DEFAULT 'default',
  `panel_name` varchar(128) NOT NULL DEFAULT 'default',
  `filters` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bimpcore_note`
--

CREATE TABLE `llx_bimpcore_note` (
  `id` int(11) NOT NULL,
  `obj_type` varchar(128) NOT NULL DEFAULT '',
  `obj_module` varchar(128) NOT NULL DEFAULT '',
  `obj_name` varchar(128) NOT NULL DEFAULT '',
  `id_obj` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `type_author` int(11) NOT NULL DEFAULT 1,
  `id_societe` int(11) NOT NULL DEFAULT 0,
  `email` varchar(256) NOT NULL DEFAULT '',
  `viewed` int(11) NOT NULL DEFAULT 1,
  `visibility` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `content` text NOT NULL,
  `date_create` datetime NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bimpcore_objects_associations`
--

CREATE TABLE `llx_bimpcore_objects_associations` (
  `id` int(10) UNSIGNED NOT NULL,
  `association` varchar(128) NOT NULL DEFAULT '',
  `src_object_module` varchar(128) NOT NULL,
  `src_object_name` varchar(128) NOT NULL,
  `src_object_type` varchar(128) NOT NULL,
  `src_id_object` int(10) UNSIGNED NOT NULL,
  `dest_object_module` varchar(128) NOT NULL,
  `dest_object_name` varchar(128) NOT NULL,
  `dest_object_type` varchar(128) NOT NULL,
  `dest_id_object` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `llx_bimpcore_timer`
--

CREATE TABLE `llx_bimpcore_timer` (
  `id` int(11) NOT NULL,
  `obj_module` varchar(128) NOT NULL,
  `obj_name` varchar(128) NOT NULL,
  `id_obj` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(128) NOT NULL,
  `time_session` int(11) NOT NULL,
  `session_start` int(11) DEFAULT NULL,
  `id_user` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `llx_bimpcore_conf`
--
ALTER TABLE `llx_bimpcore_conf`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `llx_bimpcore_list_config`
--
ALTER TABLE `llx_bimpcore_list_config`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bimpcore_list_filters`
--
ALTER TABLE `llx_bimpcore_list_filters`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bimpcore_objects_associations`
--
ALTER TABLE `llx_bimpcore_objects_associations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `llx_bimpcore_timer`
--
ALTER TABLE `llx_bimpcore_timer`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `llx_bimpcore_conf`
--
ALTER TABLE `llx_bimpcore_conf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
--
-- AUTO_INCREMENT pour la table `llx_bimpcore_file`
--
ALTER TABLE `llx_bimpcore_file`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `llx_bimpcore_history`
--
ALTER TABLE `llx_bimpcore_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `llx_bimpcore_list_config`
--
ALTER TABLE `llx_bimpcore_list_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;
--
-- AUTO_INCREMENT pour la table `llx_bimpcore_list_filters`
--
ALTER TABLE `llx_bimpcore_list_filters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `llx_bimpcore_note`
--
ALTER TABLE `llx_bimpcore_note`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `llx_bimpcore_objects_associations`
--
ALTER TABLE `llx_bimpcore_objects_associations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75993;
--
-- AUTO_INCREMENT pour la table `llx_bimpcore_timer`
--
ALTER TABLE `llx_bimpcore_timer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=345;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

