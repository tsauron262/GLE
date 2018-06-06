CREATE TABLE IF NOT EXISTS `llx_be_inventory_det` (
    `rowid`           INTEGER AUTO_INCREMENT PRIMARY KEY,
    `entity`          INTEGER DEFAULT 1,
    `tms`             TIMESTAMP,
    `date_creation`   DATETIME,
    `quantity`        INTEGER DEFAULT 1,
    `difference`      INTEGER,
    `fk_inventory`    INTEGER NOT NULL,
    `fk_user`         INTEGER NOT NULL,
    `fk_product`      INTEGER NOT NULL,
    `fk_equipment`    INTEGER NULL,
    FOREIGN KEY (`fk_inventory`) REFERENCES `llx_be_inventory`(`rowid`),
    FOREIGN KEY (`fk_user`)      REFERENCES `llx_user`(`rowid`),
    FOREIGN KEY (`fk_product`)   REFERENCES `llx_product`(`rowid`),
    FOREIGN KEY (`fk_equipment`) REFERENCES `llx_be_equipment`(`id`)
)ENGINE=innodb;