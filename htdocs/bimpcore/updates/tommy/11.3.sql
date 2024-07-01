CREATE TABLE `llx_bimp_secu_ip` (
    `id` int NOT NULL AUTO_INCREMENT,
    `tms` datetime DEFAULT CURRENT_TIMESTAMP,
    `msg` varchar(300),
    `ip` varchar(300),
    PRIMARY KEY (`id`)
);
