CREATE TABLE IF NOT EXISTS `ticket` (
    `id`            INTEGER AUTO_INCREMENT PRIMARY KEY,
    `date_creation` DATETIME,
    `fk_event`      INTEGER,
    `fk_ticket`     INTEGER,
    `fk_client`     INTEGER,
    FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`),
    FOREIGN KEY (`fk_ticket`) REFERENCES `ticket` (`id`),
    FOREIGN KEY (`fk_client`) REFERENCES `client` (`id`)
) ENGINE=innodb;