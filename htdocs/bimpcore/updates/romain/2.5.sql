--
-- Structure de la table `llx_demande_validate_comm`
--

CREATE TABLE `llx_demande_validate_comm` (
  `id` int(11) NOT NULL,
  `object` int(11) NOT NULL,
  `id_object` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `id_user_ask` int(11) NOT NULL DEFAULT 0,
  `id_user_valid` int(11) NOT NULL DEFAULT 0,
  `date_valid` datetime DEFAULT NULL,
  `type` int(11) NOT NULL,
  `user_create` int(11) NOT NULL DEFAULT 0,
  `user_update` int(11) NOT NULL DEFAULT 0,
  `date_create` datetime DEFAULT NULL,
  `date_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- Index pour la table `llx_demande_validate_comm`
--
ALTER TABLE `llx_demande_validate_comm`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour la table `llx_demande_validate_comm`
--
ALTER TABLE `llx_demande_validate_comm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `llx_demande_validate_comm` CHANGE `id_user_valid` `id_user_affected` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `llx_demande_validate_comm` ADD `id_user_valid` INT NOT NULL DEFAULT '0' AFTER `id_user_affected`;