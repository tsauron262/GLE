
ALTER TABLE `llx_c_country` ADD `in_ue` BOOLEAN NOT NULL DEFAULT FALSE; 

INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('default_id_country', (SELECT c.rowid FROM llx_c_country c WHERE c.code = 'FR'));

UPDATE `llx_c_country` SET `in_ue`= 1 WHERE `code` IN ('DE','AT','BE','BG','CY','HR','DK','ES','EE','FI','FR','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','CZ','RO','GB','SK','SI','SE');
UPDATE `llx_c_country` SET `label` = 'Nederland' WHERE label = 'Nerderland' 

ALTER TABLE `llx_bc_vente` ADD `id_user_resp` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_caisse_session`; 
UPDATE `llx_bc_vente` SET `id_user_resp` = `user_create` WHERE `id_user_resp` = 0;

