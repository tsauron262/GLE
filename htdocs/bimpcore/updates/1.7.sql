ALTER TABLE `llx_bh_ticket` ADD `id_user_resp` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_contrat`;
ALTER TABLE `llx_bh_ticket` ADD `id_contact` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_contrat`;
ALTER TABLE `llx_bh_ticket` ADD `id_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_contrat`;
ALTER TABLE `llx_bh_ticket` ADD `impact` INT NOT NULL DEFAULT '1' AFTER `ticket_number`;
ALTER TABLE `llx_bh_ticket` ADD `priorite` INT UNSIGNED NOT NULL DEFAULT '1' AFTER `ticket_number`;

ALTER TABLE `llx_bh_inter` CHANGE `priority` `priorite` INT(11) NOT NULL DEFAULT '1';

CREATE TABLE `llx_bimp_file` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `files_dir` VARCHAR(256) NOT NULL DEFAULT '',
  `file_name` varchar(128) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;