CREATE TABLE IF NOT EXISTS `llx_bv_rule` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(30) NOT NULL DEFAULT '',
    `objects` TEXT NOT NULL DEFAULT '',
    `secteurs` TEXT NOT NULL DEFAULT '',
    `val_min` DECIMAL(24,8) NOT NULL DEFAULT 0,
    `val_max` DECIMAL(24,8) NOT NULL DEFAULT 0,
    `extra_params` TEXT NOT NULL DEFAULT '',
    `users` TEXT NOT NULL DEFAULT '',
    `groups` TEXT NOT NULL DEFAULT '',
    `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
    `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
);