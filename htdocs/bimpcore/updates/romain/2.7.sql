--
-- Structure de la table `llx_bimp_notification`
--

CREATE TABLE IF NOT EXISTS `llx_bimp_notification` (
  `id` int(11) NOT NULL,
  `label` varchar(256) DEFAULT '',
  `nom` varchar(128) DEFAULT '',
  `module` varchar(128) DEFAULT '',
  `class` varchar(128) DEFAULT '',
  `method` varchar(128) DEFAULT '',
  `user_create` int(11) NOT NULL DEFAULT 0,
  `user_update` int(11) NOT NULL DEFAULT 0,
  `date_create` datetime DEFAULT NULL,
  `date_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Contenu de la table `llx_bimp_notification`
--

INSERT INTO `llx_bimp_notification` (`id`, `label`, `nom`, `module`, `class`, `method`, `user_create`, `user_update`, `date_create`, `date_update`) VALUES
(1, 'Demande de validation', 'demande_valid_comm', 'bimpvalidateorder', 'DemandeValidComm', 'getDemandeForUser', 330, 330, '2020-12-01 00:00:00', '2020-12-01 00:00:00'),
(2, 'Note', 'bimp_note', 'bimpcore', 'BimpNote', 'getNoteForUser', 330, 330, '2020-12-08 00:00:00', '2020-12-08 00:00:00'),
(3, 'Tâche', 'notif_task', 'bimptask', 'BIMP_Task', 'getTaskForUser', 330, 330, '2021-01-15 00:00:00', '2021-01-15 00:00:00');

--
-- Index pour les tables exportées
--

--
-- Index pour la table `llx_bimp_notification`
--
ALTER TABLE `llx_bimp_notification`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `llx_bimp_notification`
--
ALTER TABLE `llx_bimp_notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;