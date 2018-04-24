CREATE TABLE IF NOT EXISTS `user` (
    `id`                    INTEGER AUTO_INCREMENT PRIMARY KEY,
    `first_name`            VARCHAR(255) NOT NULL,
    `last_name`             VARCHAR(255) NOT NULL,
    `email`                 VARCHAR(255) UNIQUE NOT NULL,
    `login`                 VARCHAR(255) UNIQUE NOT NULL,
    `pass_word`             VARCHAR(255) NOT NULL,
    `status`                INTEGER DEFAULT 0,
    `create_event_tariff`   INTEGER DEFAULT 0,
    `reserve_ticket`        INTEGER DEFAULT 0,
    `validate_event`        INTEGER DEFAULT 0
) ENGINE=innodb;