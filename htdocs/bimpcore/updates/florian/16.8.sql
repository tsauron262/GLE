CREATE TABLE IF NOT EXISTS `llx_be_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `label` varchar(255) NOT NULL DEFAULT '',
  `ref` varchar(255) NOT NULL DEFAULT '',
  `products` longtext NOT NULL,
  `equipments` longtext NOT NULL,
  `user_create` int(11) unsigned NOT NULL DEFAULT 0,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(11) NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE IF NOT EXISTS `llx_be_package_place` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_package` int(10) unsigned NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `id_client` int(10) unsigned NOT NULL DEFAULT 0,
  `id_contact` int(10) unsigned NOT NULL DEFAULT 0,
  `id_entrepot` int(10) unsigned NOT NULL DEFAULT 0,
  `code_centre` varchar(12) NOT NULL DEFAULT '',
  `id_user` int(10) unsigned NOT NULL DEFAULT 0,
  `place_name` varchar(256) NOT NULL DEFAULT '',
  `infos` text NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `code_mvt` varchar(128) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT 0,
  `date_create` datetime NOT NULL DEFAULT current_timestamp(),
  `user_create` int(10) unsigned NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `user_update` int(11) NOT NULL DEFAULT 0
);