
-- SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- SET time_zone = "+00:00";

--
-- Structure de la table `llx_bt_transfer_det`
--

CREATE TABLE `llx_bt_transfer_det` (
  `id` int(11) NOT NULL,
  `entity` int(11) DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_transfer` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_equipment` int(11) DEFAULT NULL,
  `quantity_sent` int(11) NOT NULL,
  `quantity_received` int(11) DEFAULT '0',
  `quantity_transfered` int(11) NOT NULL DEFAULT '0',
  `user_update` int(11) NOT NULL,
  `user_create` int(11) NOT NULL,
  `date_update` datetime NOT NULL,
  `date_create` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Index pour la table `llx_bt_transfer_det`
--
ALTER TABLE `llx_bt_transfer_det`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transfer` (`id_transfer`),
  ADD KEY `fk_user_create` (`user_create`),
  ADD KEY `fk_product` (`id_product`),
  ADD KEY `fk_equipment` (`id_equipment`),
  ADD KEY `fk_user_update_det` (`user_update`);

--
-- AUTO_INCREMENT pour la table `llx_bt_transfer_det`
--
ALTER TABLE `llx_bt_transfer_det`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `llx_bt_transfer_det`
--
ALTER TABLE `llx_bt_transfer_det`
  ADD CONSTRAINT `fk_user_update_det` FOREIGN KEY (`user_update`) REFERENCES `llx_user` (`rowid`);

