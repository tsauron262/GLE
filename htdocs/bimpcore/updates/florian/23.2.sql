CREATE TABLE `llx_bimp_c_secteur` (
  `clef` varchar(30) NOT NULL,
  `valeur` varchar(200) NOT NULL,
  `email_from` varchar(30) NOT NULL DEFAULT '',
  `public_entity` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`clef`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;