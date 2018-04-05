CREATE TABLE IF NOT EXISTS `client` (
    `id`               INTEGER AUTO_INCREMENT PRIMARY KEY,
    `date_inscription` DATETIME,
    `date_born`        DATETIME,
    `first_name`       VARCHAR(255) NOT NULL,
    `last_name`        VARCHAR(255) NOT NULL,
    `email`            VARCHAR(255) UNIQUE NOT NULL
) ENGINE=innodb;