CREATE TABLE IF NOT EXISTS `llx_bimpapi_api` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `id_default_user_account_test` int(11) NOT NULL DEFAULT 0,
  `id_default_user_account_prod` int(11) NOT NULL DEFAULT 0,
  `processes` TEXT NOT NULL DEFAULT '',
  `active` int(1) NOT NULL DEFAULT 0,
  `mode` varchar(255) NOT NULL DEFAULT 'test',
  `connect_timeout` int(11) NOT NULL DEFAULT 10,
  `exec_timeout` int(11) NOT NULL DEFAULT 30,
  `log_errors` int(1) NOT NULL DEFAULT 0,
  `log_requests` int(1) NOT NULL DEFAULT 0,
  `notify_defuser_unauth` int(1) NOT NULL DEFAULT 0
);

ALTER TABLE `llx_bimpapi_api` ADD INDEX(`id_default_user_account_test`);
ALTER TABLE `llx_bimpapi_api` ADD INDEX(`id_default_user_account_prod`);

CREATE TABLE IF NOT EXISTS `llx_bimpapi_api_param` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_api` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `value` TEXT NOT NULL DEFAULT '',
  `crypted` int(1) NOT NULL DEFAULT 0
);

ALTER TABLE `llx_bimpapi_api_param` ADD INDEX(`id_api`); 

CREATE TABLE IF NOT EXISTS `llx_bimpapi_user_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_api` int(11) NOT NULL DEFAULT 0,
  `users` TEXT NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `login` varchar(255) NOT NULL DEFAULT '',
  `pword` varchar(255) NOT NULL DEFAULT '',
  `tokens` TEXT NOT NULL DEFAULT '',
  `logged_end` datetime DEFAULT NULL
);

ALTER TABLE `llx_bimpapi_user_account` ADD INDEX(`id_api`);