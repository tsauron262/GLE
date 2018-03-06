CREATE TABLE IF NOT EXISTS `llx_be_transfer_det` (
    `rowid`             INTEGER AUTO_INCREMENT PRIMARY KEY,
    `entity`            INTEGER DEFAULT 1,
    `tms`               TIMESTAMP,
    `date_opening`      DATETIME,
    `quantity_sent`     INTEGER NOT NULL,
    `quantity_received` INTEGER DEFAULT 0,
    `fk_transfer`       INTEGER NOT NULL,
    `fk_user_create`    INTEGER NOT NULL,
    `fk_product`        INTEGER NOT NULL,
    `fk_equipment`      INTEGER NULL,
    FOREIGN KEY (`fk_transfer`)    REFERENCES `llx_be_transfer`(`rowid`),
    FOREIGN KEY (`fk_user_create`) REFERENCES `llx_user`(`rowid`),
    FOREIGN KEY (`fk_product`)     REFERENCES `llx_product`(`rowid`),
    FOREIGN KEY (`fk_equipment`)   REFERENCES `llx_be_equipment`(`id`)
)ENGINE=innodb;