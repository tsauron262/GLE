CREATE TABLE IF NOT EXISTS `llx_concurrence_rdc` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `fk_soc` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `site` varchar(255) NOT NULL DEFAULT '',
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(11) NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `llx_ca_rdc` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_obj` int(11) NOT NULL DEFAULT 0,
  `type_obj` int(11) NOT NULL DEFAULT 0,
  `ca` decimal(24,8) NOT NULL DEFAULT 0,
  `fk_category` int(11) NOT NULL DEFAULT 0,
  `fk_period` int(11) NOT NULL DEFAULT 0,
  `debut_period` date DEFAULT NULL,
  `user_update` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(11) NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `llx_c_categorie_rdc` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `libelle` varchar(50) NOT NULL,
  `ordre` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
);
INSERT INTO `llx_c_categorie_rdc` (libelle, ordre) VALUES
	('Catégorie 1', 10),
	('Catégorie 2', 20),
	('Catégorie 3', 30),
	('Catégorie 4', 40);
