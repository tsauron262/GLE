CREATE TABLE IF NOT EXISTS `event` (
    `id`            INTEGER AUTO_INCREMENT PRIMARY KEY,
    `label`         VARCHAR(255),
    `date_creation` DATETIME,
    `date_start`    DATETIME,
    `date_end`      DATETIME
) ENGINE=innodb;