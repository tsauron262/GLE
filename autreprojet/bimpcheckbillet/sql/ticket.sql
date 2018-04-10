CREATE TABLE IF NOT EXISTS `ticket` (
    `id`            INTEGER AUTO_INCREMENT PRIMARY KEY,
    `date_creation` DATETIME,
    `fk_event`      INTEGER NOT NULL,
    `fk_tariff`     INTEGER NOT NULL,
    `fk_client`     INTEGER NOT NULL,
    `barcode`       VARCHAR(255) NOT NULL,
    FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`),
    FOREIGN KEY (`fk_tariff`) REFERENCES `tariff` (`id`),
    FOREIGN KEY (`fk_client`) REFERENCES `client` (`id`)
) ENGINE=innodb;