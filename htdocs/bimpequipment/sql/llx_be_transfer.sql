CREATE TABLE IF NOT EXISTS `llx_be_transfer` (
    `rowid`           INTEGER AUTO_INCREMENT PRIMARY KEY,
    `entity`          INTEGER DEFAULT 1,
    `tms`             TIMESTAMP,
    `status`          INTEGER NOT NULL,
    `date_opening`    DATETIME,
    `date_closing`    DATETIME,
    `fk_warehouse`    INTEGER NOT NULL,
    `fk_user_create`  INTEGER NOT NULL,
    FOREIGN KEY (`fk_warehouse`)   REFERENCES `llx_entrepot`(`rowid`),
    FOREIGN KEY (`fk_user_create`) REFERENCES `llx_user`(`rowid`)
) ENGINE=innodb;