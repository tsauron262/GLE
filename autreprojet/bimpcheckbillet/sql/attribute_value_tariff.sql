CREATE TABLE IF NOT EXISTS `attribute_value_tariff` (
    `id`                    INTEGER AUTO_INCREMENT PRIMARY KEY,
    `fk_tariff`             INTEGER NOT NULL,
    `fk_attribute_value`    INTEGER NOT NULL,
    `price`                 FLOAT,
    `number_place`          INTEGER,
    FOREIGN KEY (`fk_tariff`) REFERENCES `tariff` (`id`),
    FOREIGN KEY (`fk_attribute_value`) REFERENCES `attribute_value` (`id`)
) ENGINE=innodb;