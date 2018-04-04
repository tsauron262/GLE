CREATE TABLE IF NOT EXISTS `tariff` (
    `id`            INTEGER AUTO_INCREMENT PRIMARY KEY,
    `label`         VARCHAR(255),
    `date_creation` DATETIME,
    `price`         FLOAT,
    `fk_event`      INTEGER,
    FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`)
) ENGINE=innodb;