CREATE TABLE IF NOT EXISTS `event` (
    `id`            INTEGER AUTO_INCREMENT PRIMARY KEY,
    `label`         VARCHAR(255) NOT NULL,
    `date_creation` DATETIME NOT NULL,
    `date_start`    DATETIME NOT NULL,
    `date_end`      DATETIME NOT NULL,
    `description`   TEXT,
    `status`        INTEGER NOT NULL DEFAULT 0
) ENGINE=innodb;