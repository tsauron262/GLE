CREATE TABLE IF NOT EXISTS `llx_be_inventory` (
    `rowid`           INTEGER AUTO_INCREMENT PRIMARY KEY,
    `entity`          INTEGER DEFAULT 1,
    `tms`             TIMESTAMP,
    `date_ouverture`  DATETIME,
    `date_fermeture`  DATETIME,
    `statut`          INTEGER NOT NULL,
    `fk_entrepot`     INTEGER NOT NULL,
    `fk_user_create`  INTEGER NOT NULL,
    FOREIGN KEY (`fk_entrepot`)    REFERENCES `llx_entrepot`(`rowid`),
    FOREIGN KEY (`fk_user_create`) REFERENCES `llx_user`(`rowid`)
)ENGINE=innodb;