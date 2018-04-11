CREATE TABLE IF NOT EXISTS `user` (
    `id`               INTEGER AUTO_INCREMENT PRIMARY KEY,
--     `date_inscription` DATETIME,
--     `date_born`        DATETIME,
--     `first_name`       VARCHAR(255) NOT NULL,
--     `last_name`        VARCHAR(255) NOT NULL,
--     `email`            VARCHAR(255) UNIQUE NOT NULL,
    `login`            VARCHAR(255) NOT NULL,
    `password`         VARCHAR(255) NOT NULL,
    `status`           INTEGER
) ENGINE=innodb;