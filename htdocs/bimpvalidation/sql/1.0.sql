CREATE TABLE IF NOT EXISTS `llx_bv_rule` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(30) NOT NULL DEFAULT '',
    `all_objects` tinyint(1) NOT NULL DEFAULT 0,
    `objects` TEXT NOT NULL DEFAULT '',
    `all_secteurs` tinyint(1) NOT NULL DEFAULT 0,
    `secteurs` TEXT NOT NULL DEFAULT '',
    `val_min` DECIMAL(24,8) NOT NULL DEFAULT 0,
    `val_max` DECIMAL(24,8) NOT NULL DEFAULT 0,
    `extra_params` TEXT NOT NULL DEFAULT '',
    `user_superior` tinyint(1) NOT NULL DEFAULT 0,
    `all_users` tinyint(1) NOT NULL DEFAULT 0,
    `users` TEXT NOT NULL DEFAULT '',
    `groups` TEXT NOT NULL DEFAULT '',
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
    `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS `llx_bv_demande` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `status` int(11) NOT NULL DEFAULT 0,
    `type_validation` VARCHAR(30) NOT NULL DEFAULT '',
    `type_object` VARCHAR(30) NOT NULL DEFAULT '',
    `id_object` int(11) NOT NULL DEFAULT 0,
    `id_user_demande` int(11) NOT NULL DEFAULT 0,
    `validation_users` TEXT NOT NULL DEFAULT '',
    `id_user_affected` int(11) NOT NULL DEFAULT 0,
    `id_user_validate` int(11) NOT NULL DEFAULT 0,
    `date_validate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `comment` TEXT NOT NULL DEFAULT '',
    `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
    `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
);