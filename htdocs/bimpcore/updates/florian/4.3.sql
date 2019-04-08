
CREATE TABLE IF NOT EXISTS `llx_be_user_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL DEFAULT '',
  `id_client_contact` INT UNSIGNED NOT NULL DEFAULT '0',
  `id_client` INT UNSIGNED NOT NULL DEFAULT '0',
  `login` varchar(128) NOT NULL DEFAULT '',
  `pword` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB;



ALTER TABLE `llx_be_equipment` ADD `product_label` VARCHAR(256) NOT NULL DEFAULT '' AFTER `id_product`;
