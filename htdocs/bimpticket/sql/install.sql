CREATE TABLE IF NOT EXISTS `llx_btk_ticket` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
--     `ref` VARCHAR(255) NOT NULL DEFAULT '',
    `status` int(11) NOT NULL DEFAULT 0,
	`type` int(5) NOT NULL DEFAULT 0,
    `id_client` int(11) NOT NULL DEFAULT 0,
	`id_contact` int(11) NOT NULL DEFAULT 0,
    `id_user_assigned` int(11) NOT NULL DEFAULT 0,
	`sujet` TEXT NOT NULL DEFAULT '',
    `date_closed` date default null,
    `delai_resolution` int(11) NOT NULL DEFAULT 0,
    `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
    `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`entity` int(11) NOT NULL DEFAULT 1
);
