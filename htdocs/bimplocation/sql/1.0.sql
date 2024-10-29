CREATE TABLE IF NOT EXISTS `llx_bimp_location` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `ref` VARCHAR(255) NOT NULL DEFAULT '',
    `status` int(11) NOT NULL DEFAULT 0,
    `id_client` int(11) NOT NULL DEFAULT 0,
    `id_contact_facturation` int(11) NOT NULL DEFAULT 0,
    `id_facture_acompte` int(11) NOT NULL DEFAULT 0,
    `date_from` date default null,
    `date_to` date default null,
    `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
    `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `llx_bimp_location_line` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_location` int(11) NOT NULL DEFAULT 0,
    `id_equipment` int(11) NOT NULL DEFAULT 0,
    `id_forfait` int(11) NOT NULL DEFAULT 0,
    `date_from` date default null,
    `date_to` date default null,
    `infos` TEXT NOT NULL DEFAULT ''
);