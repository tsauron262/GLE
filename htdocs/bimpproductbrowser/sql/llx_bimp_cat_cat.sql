

CREATE TABLE IF NOT EXISTS `llx_bimp_cat_cat` (
	`id` 				integer NOT NULL,
	`fk_parent_cat`		integer NOT NULL,
	`fk_child_cat`		integer NOT NULL,
	`import_key`		varchar(14)
)ENGINE=innodb;