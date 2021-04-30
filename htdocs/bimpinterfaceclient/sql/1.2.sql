ALTER TABLE `llx_bic_user` CHANGE `date_creation` `date_create` DATETIME NOT NULL;

ALTER TABLE `llx_bic_user` CHANGE `user_creation` `user_create` int NOT NULL;

ALTER TABLE `llx_bic_user` CHANGE `date_modification` `date_update` DATETIME NOT NULL;

ALTER TABLE `llx_bic_user` CHANGE `user_modification` `user_update` int NOT NULL;