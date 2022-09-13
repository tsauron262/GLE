CREATE TABLE IF NOT EXISTS `llx_bf_demande` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_client` int(11) NOT NULL DEFAULT 0,
  `id_contact_client` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `label` VARCHAR(255) NOT NULL DEFAULT '',
  `duration` int(11) NOT NULL DEFAULT 0,
  `periodicity` int(11) NOT NULL DEFAULT 0,
  `mode_calcul` int(11) NOT NULL DEFAULT 0,
  `vr_achat` double(24,8) NOT NULL DEFAULT 0,
  `vr_vente` double(24,8) NOT NULL DEFAULT 0,
  `ca_prevu` double(24,8) NOT NULL DEFAULT 0,
  `pba_prevu` double(24,8) NOT NULL DEFAULT 0,
  `date_loyer` datetime NULL DEFAULT NULL,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_create` int(11) NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(11) NOT NULL DEFAULT 0,
  KEY `id_client` (`id_client`),
  KEY `id_contact_client` (`id_contact_client`)
);

CREATE TABLE IF NOT EXISTS `llx_bf_demande_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_demande` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 0,
  `id_product` int(11) NOT NULL DEFAULT 0,
  `label` text NOT NULL DEFAULT '',
  `qty` double(24,8) NOT NULL DEFAULT 0,
  `pu_ht` double(24,8) NOT NULL DEFAULT 0,
  `tva_tx` double(24,8) NOT NULL DEFAULT 0,
  `pa_ht` double(24,8) NOT NULL DEFAULT 0,
  `remise` double(24,8) NOT NULL DEFAULT 0,
  `total_ht` double(24,8) NOT NULL DEFAULT 0,
  `total_ttc` double(24,8) NOT NULL DEFAULT 0,
  `serialisable` tinyint(1) NOT NULL DEFAULT 0,
  `equipments` mediumtext NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT 0,
  KEY `id_demande` (`id_demande`),
  KEY `id_product` (`id_product`)
);

CREATE TABLE IF NOT EXISTS `llx_bf_refinanceur` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_societe` int(11) NOT NULL DEFAULT 0,
  `url_demande` VARCHAR(255) NOT NULL DEFAULT '',
  KEY `id_societe` (`id_societe`)
);

CREATE TABLE IF NOT EXISTS `llx_bf_demande_refinanceur` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_demande` int(11) NOT NULL DEFAULT 0,
  `id_refinanceur` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `comment` text NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_create` int(11) NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(11) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  KEY `id_demande` (`id_demande`),
  KEY `id_refinanceur` (`id_refinanceur`)
);
