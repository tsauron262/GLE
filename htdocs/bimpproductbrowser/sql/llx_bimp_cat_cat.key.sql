ALTER TABLE `llx_bimp_cat_cat` ADD PRIMARY KEY `pk_cat_cat` (`id`);

ALTER TABLE `llx_bimp_cat_cat` ADD CONSTRAINT `cont_fk_parent_cat` FOREIGN KEY (`fk_parent_cat`) REFERENCES `llx_categorie` (`rowid`);
ALTER TABLE `llx_bimp_cat_cat` ADD CONSTRAINT `cont_fk_child_cat` FOREIGN KEY (`fk_child_cat`) REFERENCES `llx_categorie` (`rowid`);
