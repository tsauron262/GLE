CREATE TABLE IF NOT EXISTS `client` (
    `id`               INTEGER AUTO_INCREMENT PRIMARY KEY,
    `date_inscription` DATETIME,
    `date_born`        DATETIME,
    `firstname`        VARCHAR(255),
    `lastname`         VARCHAR(255),
    `email`            VARCHAR(255)
) ENGINE=innodb;