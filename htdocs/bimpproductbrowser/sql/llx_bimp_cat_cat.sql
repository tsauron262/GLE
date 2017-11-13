DROP TABLE IF EXISTS `llx_bimp_cat_cat`;

CREATE TABLE `llx_bimp_cat_cat` (
	`rowid`			INTEGER AUTO_INCREMENT PRIMARY KEY,
	`entity`		INTEGER default 1,
	`datec`			DATETIME,
	`tms`           TIMESTAMP,
	`fk_parent_cat`	INTEGER NOT NULL,
	`fk_child_cat`	INTEGER NOT NULL,
	`import_key`	VARCHAR(14)
)ENGINE=innodb;