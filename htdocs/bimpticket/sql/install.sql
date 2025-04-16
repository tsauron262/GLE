ALTER TABLE `llx_ticket`
	ADD `fk_user_update` int(11) NOT NULL DEFAULT 0 AFTER `fk_user_create`,
	ADD `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `datec`;
