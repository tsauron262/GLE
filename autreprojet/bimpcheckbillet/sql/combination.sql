CREATE TABLE IF NOT EXISTS `combination` (
    `id`                    INTEGER AUTO_INCREMENT PRIMARY KEY,
    `label`                 VARCHAR(255) NOT NULL,
    `id_combination_extern` INTEGER,
    `price`                 FLOAT,
    `number_place`          INTEGER
) ENGINE=innodb;