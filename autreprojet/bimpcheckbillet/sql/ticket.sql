CREATE TABLE IF NOT EXISTS `ticket` (
    `id`            INTEGER AUTO_INCREMENT PRIMARY KEY,
    `date_creation` DATETIME,
    `fk_event`      INTEGER NOT NULL,
    `fk_tariff`     INTEGER NOT NULL,
    `fk_user`       INTEGER NOT NULL,
    `date_scan`     DATETIME,
    `barcode`       VARCHAR(255) NOT NULL UNIQUE,
    `first_name`    VARCHAR(255),
    `last_name`     VARCHAR(255),
    `price`         FLOAT,
    `extra_int1`    INTEGER,
    `extra_int2`    INTEGER,
    `extra_string1` VARCHAR(255),
    `extra_string2` VARCHAR(255),
    FOREIGN KEY (`fk_event`)  REFERENCES `event` (`id`),
    FOREIGN KEY (`fk_tariff`) REFERENCES `tariff` (`id`),
    FOREIGN KEY (`fk_user`)   REFERENCES `user` (`id`)
) ENGINE=innodb;