DROP TABLE `llx_contact_saved_data`;
ALTER TABLE `llx_societe_saved_data` ADD `type` VARCHAR(10) NOT NULL DEFAULT 'societe' AFTER `id`;
ALTER TABLE `llx_societe_saved_data` CHANGE `id_client` `id_object` int(11) NOT NULL DEFAULT 0;