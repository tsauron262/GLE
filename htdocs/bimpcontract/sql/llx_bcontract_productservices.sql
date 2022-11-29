CREATE TABLE IF NOT EXISTS `llx_bcontract_productservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(128) NOT NULL default "",
  `content` text,
  `active` boolean NOT NULL default 0,
  `use_in_contract` int(11) NOT NULL default 1,
  `use_in_commercial` int(11) NOT NULL default 0,
  `test` VARCHAR(255) default NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT "0",
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT "0",
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

