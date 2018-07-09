CREATE TABLE IF NOT EXISTS `attribute_value` (
    `id`                        INTEGER AUTO_INCREMENT PRIMARY KEY,
    `label`                     VARCHAR(255) NOT NULL,
    `id_attribute_parent`       INTEGER NOT NULL,
    `id_attribute_value_extern` INTEGER NOT NULL
) ENGINE=innodb;