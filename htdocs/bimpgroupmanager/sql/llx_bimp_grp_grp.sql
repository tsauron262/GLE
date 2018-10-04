CREATE TABLE IF NOT EXISTS `llx_bimp_grp_grp` (
    `rowid`         INTEGER AUTO_INCREMENT PRIMARY KEY,
    `entity`        INTEGER default 1,
    `datec`         DATETIME,
    `tms`           TIMESTAMP,
    `fk_parent`     INTEGER NOT NULL,
    `fk_child`      INTEGER NOT NULL,
    `import_key`    VARCHAR(14)
)ENGINE=innodb;