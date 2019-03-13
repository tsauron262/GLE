ALTER TABLE `llx_be_user_account` ADD `id_client_contact` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `name`;
ALTER TABLE `llx_be_user_account` ADD `id_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `name`;

ALTER TABLE `llx_be_equipment_logiciel` DROP `id_user_account`;
