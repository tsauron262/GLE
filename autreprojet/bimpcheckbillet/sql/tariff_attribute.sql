CREATE TABLE IF NOT EXISTS `tariff_attribute` (
    `id`                INTEGER AUTO_INCREMENT PRIMARY KEY,
    `fk_tariff`         INTEGER NOT NULL,
    `fk_attribute`      INTEGER NOT NULL,
    FOREIGN KEY (`fk_tariff`) REFERENCES `tariff` (`id`),
    FOREIGN KEY (`fk_attribute`) REFERENCES `attribute` (`id`)
) ENGINE=innodb;