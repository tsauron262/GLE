CREATE TABLE IF NOT EXISTS `tariff` (
    `id`            INTEGER AUTO_INCREMENT PRIMARY KEY,
    `label`         VARCHAR(255) NOT NULL,
    `date_creation` DATETIME,
    `price`         FLOAT NOT NULL,
    `fk_event`      INTEGER NOT NULL,
    FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`)
) ENGINE=innodb;