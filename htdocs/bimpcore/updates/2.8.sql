ALTER TABLE `llx_be_user_account` ADD `id_client_contact` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `name`;
ALTER TABLE `llx_be_user_account` ADD `id_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `name`;

ALTER TABLE `llx_be_equipment_logiciel` DROP `id_user_account`;

CREATE TABLE IF NOT EXISTS `llx_bmp_calc_montant_type_montant` (
  `id_calc_montant` int(10) UNSIGNED NOT NULL,
  `id_type_montant` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB;