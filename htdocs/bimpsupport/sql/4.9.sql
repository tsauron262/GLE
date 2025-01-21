CREATE TABLE if not exists
llx_bs_centre_sav (
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`label` varchar(255) NOT NULL DEFAULT '',
	`tel` varchar(255) NOT NULL DEFAULT '',
	`email` varchar(255) NOT NULL DEFAULT '',
	`shipTo` varchar(255) NOT NULL DEFAULT '',
	`zip` varchar(255) NOT NULL DEFAULT '',
	`address` text NOT NULL DEFAULT '',
	`id_entrepot` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
)
