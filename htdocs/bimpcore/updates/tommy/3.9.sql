CREATE TABLE `llx_bimp_php_session` (
    `id_session` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
    `data` varchar(10000) COLLATE utf8_unicode_ci DEFAULT NULL,
    `creation` datetime DEFAULT CURRENT_TIMESTAMP,
    `update` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_session`)
);