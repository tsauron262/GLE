--
-- Structure de la table `llx_bl_inventory`
--
CREATE TABLE `llx_bl_inventory` (
  `id` int(11) NOT NULL,
  `date_opening` datetime DEFAULT NULL,
  `date_closing` datetime DEFAULT NULL,
  `status` int(11) NOT NULL,
  `fk_warehouse` int(11) NOT NULL,
  `user_create` int(11) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_create` datetime NOT NULL,
  `user_update` int(11) NOT NULL,
  `date_update` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Index pour la table `llx_bl_inventory`
ALTER TABLE `llx_bl_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_entrepot` (`fk_warehouse`),
  ADD KEY `fk_user_create` (`user_create`);

-- AUTO_INCREMENT pour la table `llx_bl_inventory`
ALTER TABLE `llx_bl_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


--
-- Structure de la table `llx_bl_inventory_det`
--
CREATE TABLE `llx_bl_inventory_det` (
  `id` int(11) NOT NULL,
  `fk_inventory` int(11) NOT NULL,
  `fk_product` int(11) NOT NULL,
  `fk_equipment` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT '1',
  `date_create` datetime DEFAULT NULL,
  `user_create` int(11) NOT NULL,
  `user_update` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Index pour la table `llx_bl_inventory_det`
ALTER TABLE `llx_bl_inventory_det`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inventory` (`fk_inventory`),
  ADD KEY `fk_user` (`user_create`),
  ADD KEY `fk_product` (`fk_product`),
  ADD KEY `fk_equipment` (`fk_equipment`),
  ADD KEY `user_update` (`user_update`);

-- AUTO_INCREMENT pour la table `llx_bl_inventory_det`
ALTER TABLE `llx_bl_inventory_det`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;