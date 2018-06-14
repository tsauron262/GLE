CREATE TABLE IF NOT EXISTS `tariff_combination` (
    `id`                INTEGER AUTO_INCREMENT PRIMARY KEY,
    `fk_tariff`         INTEGER NOT NULL,
    `fk_combination`    INTEGER NOT NULL,
    FOREIGN KEY (`fk_tariff`) REFERENCES `tariff` (`id`),
    FOREIGN KEY (`fk_combination`) REFERENCES `combination` (`id`)
) ENGINE=innodb;