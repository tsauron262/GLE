CREATE TABLE IF NOT EXISTS `attribute` (
    `id`                    INTEGER AUTO_INCREMENT PRIMARY KEY,
    `label`                 VARCHAR(255) NOT NULL,
    `type`                  VARCHAR(255) NOT NULL,
    `id_attribute_extern`   INTEGER NOT NULL
) ENGINE=innodb;