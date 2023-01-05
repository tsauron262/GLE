
-- SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- SET time_zone = "+00:00";

--
-- Structure de la table `llx_bt_transfer`
--

CREATE TABLE `llx_bt_transfer` (
  `id` int(11) NOT NULL,
  `entity` int(11) DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` int(11) NOT NULL,
  `date_opening` datetime DEFAULT NULL,
  `date_closing` datetime DEFAULT NULL,
  `id_warehouse_source` int(11) NOT NULL,
  `id_warehouse_dest` int(11) NOT NULL,
  `user_create` int(11) NOT NULL DEFAULT '330',
  `user_update` int(11) NOT NULL,
  `date_create` datetime NOT NULL,
  `date_update` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Index pour la table `llx_bt_transfer`
--
ALTER TABLE `llx_bt_transfer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_warehouse_source` (`id_warehouse_source`),
  ADD KEY `fk_warehouse_dest` (`id_warehouse_dest`),
  ADD KEY `fk_user_create` (`user_create`),
  ADD KEY `fk_user_update` (`user_update`);

--
-- AUTO_INCREMENT pour la table `llx_bt_transfer`
--
ALTER TABLE `llx_bt_transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Contraintes pour la table `llx_bt_transfer`
--
ALTER TABLE `llx_bt_transfer`
  ADD CONSTRAINT `fk_user_create` FOREIGN KEY (`user_create`) REFERENCES `llx_user` (`rowid`),
  ADD CONSTRAINT `fk_user_update` FOREIGN KEY (`user_update`) REFERENCES `llx_user` (`rowid`);
