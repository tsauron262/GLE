--
-- Structure de la table `llx_validate_comm`
--

CREATE TABLE `llx_validate_comm` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `secteur` varchar(11) NOT NULL,
  `type` int(11) NOT NULL,
  `object` int(11) NOT NULL,
  `val_max` float NOT NULL,
  `val_min` float NOT NULL,
  `user_create` int(11) NOT NULL DEFAULT 0,
  `user_update` int(11) NOT NULL DEFAULT 0,
  `date_create` datetime DEFAULT NULL,
  `date_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Index pour la table `llx_validate_comm`
--
ALTER TABLE `llx_validate_comm`
  ADD PRIMARY KEY (`id`);
--
-- AUTO_INCREMENT pour la table `llx_validate_comm`
--
ALTER TABLE `llx_validate_comm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;