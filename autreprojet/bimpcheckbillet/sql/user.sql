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

-- Default users (admin and prestashop)
INSERT INTO `user` (`id`, `first_name`, `last_name`, `email`, `login`, `pass_word`, `status`)
    VALUES (1, "admin", "admin", "admin", "root", "toor", 2);
INSERT INTO `user` (`id`, `first_name`, `last_name`, `email`, `login`, `pass_word`, `status`)
    VALUES (2, "prestashop", "prestashop", "prestashop", "prestashop", "C0SV6UQumTADcq4EGgMsBviFM27oBJ6P", 1);
