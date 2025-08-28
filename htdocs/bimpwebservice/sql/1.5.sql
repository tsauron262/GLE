CREATE TABLE IF NOT EXISTS `llx_bws_user_token` (
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`id_ws_user` int(11) NOT NULL DEFAULT 0,
	`token` varchar(255) NOT NULL DEFAULT '',
	`token_expire` datetime DEFAULT NULL
);
