ALTER TABLE `llx_bh_ticket` ADD `id_user_resp` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_contrat`;
ALTER TABLE `llx_bh_ticket` ADD `id_contact` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_contrat`;
ALTER TABLE `llx_bh_ticket` ADD `id_client` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `id_contrat`;
ALTER TABLE `llx_bh_ticket` ADD `impact` INT NOT NULL DEFAULT '1' AFTER `ticket_number`;
ALTER TABLE `llx_bh_ticket` ADD `priorite` INT UNSIGNED NOT NULL DEFAULT '1' AFTER `ticket_number`;

ALTER TABLE `llx_bh_inter` CHANGE `priority` `priorite` INT(11) NOT NULL DEFAULT '1';

