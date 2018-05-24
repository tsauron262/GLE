CREATE TABLE IF NOT EXISTS `event_admin` (
    `id`            INTEGER AUTO_INCREMENT PRIMARY KEY,
    `fk_event`      INTEGER NOT NULL,
    `fk_user`       INTEGER NOT NULL,
    FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`),
    FOREIGN KEY (`fk_user`) REFERENCES  `user` (`id`)
) ENGINE=innodb;