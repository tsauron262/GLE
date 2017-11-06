

CREATE TABLE IF NOT EXISTS `llx_bimp_cat_cat` (
	`id` 				int(11) NOT NULL AUTO_INCREMENT,
	`fk_parent_cat`		integer NOT NULL,
	`fk_child_cat`		integer NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`fk_parent_cat`) REFERENCES llx_categorie(rowid),
	FOREIGN KEY (`fk_child_cat`) REFERENCES llx_categorie(rowid)
)ENGINE=innodb;