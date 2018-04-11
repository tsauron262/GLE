CREATE TABLE IF NOT EXISTS `tariff` (
    `id`                INTEGER AUTO_INCREMENT PRIMARY KEY,
    `label`             VARCHAR(255) NOT NULL,
    `date_creation`     DATETIME,
    `date_start`        DATETIME NOT NULL,
    `date_end`          DATETIME NOT NULL,
    `price`             FLOAT NOT NULL,
    `extra_int1`        INTEGER,
    `extra_int2`        INTEGER,
    `extra_string1`     VARCHAR(255),
    `extra_string2`     VARCHAR(255),
    `id_prod_extern`    INTEGER,
    `img_name`          VARCHAR(255),
    `fk_event`          INTEGER NOT NULL,
    FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`)
) ENGINE=innodb;