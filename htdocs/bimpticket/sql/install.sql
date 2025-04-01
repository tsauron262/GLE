ALTER TABLE `llx_ticket`
	ADD `type` int(5) NOT NULL DEFAULT 0 AFTER `ref`,
	ADD `fk_user_update` int(11) NOT NULL DEFAULT 0 AFTER `fk_user_create`,
	ADD `delai_resolution` int(11) NOT NULL DEFAULT 0 AFTER `resolution`,
	ADD `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `datec`;
