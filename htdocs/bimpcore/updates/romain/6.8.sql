ALTER TABLE `llx_bimpalert_produit` CHANGE `id_user_notif` `notified_user` TEXT NOT NULL DEFAULT '';
ALTER TABLE `llx_bimpalert_produit` ADD `notified_group` TEXT NOT NULL DEFAULT '' AFTER `notified_user`; 
ALTER TABLE `llx_bimpalert_produit` ADD `type_action` VARCHAR(31) NULL DEFAULT '' AFTER `type_piece`;