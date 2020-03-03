ALTER TABLE `llx_c_actioncomm` ADD `icon` VARCHAR(255) NOT NULL DEFAULT 'fas_calendar-check'; 

UPDATE `llx_c_actioncomm` SET `icon` = 'fas_phone' WHERE `code` IN ('AC_TEL');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_fax' WHERE `code` IN ('AC_FAX');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_at' WHERE `code` IN ('AC_EMAIL');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_business-time' WHERE `code` IN ('AC_RDV');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_ambulance' WHERE `code` IN ('AC_INT', 'INTER', 'INTER_SG', 'DEP_EXT', 'VIS_CTR');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_chalkboard-teacher' WHERE `code` IN ('FORM_INT', 'FORM_EXT', 'FORM_CERTIF');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_calendar-day' WHERE `code` IN ('CONGES', 'RTT_DEM');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_tasks' WHERE `code` IN ('TACHE');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_user-shield' WHERE `code` IN ('PERSO');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_laptop-medical' WHERE `code` IN ('TELE');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_users' WHERE `code` IN ('REU_INT');
UPDATE `llx_c_actioncomm` SET `icon` = 'fas_headset' WHERE `code` IN ('HOT');


ALTER TABLE `llx_propaldet` ADD INDEX(`fk_remise_except`);
ALTER TABLE `llx_commandedet` ADD INDEX(`fk_remise_except`);
ALTER TABLE `llx_facturedet` ADD INDEX(`fk_remise_except`);

ALTER TABLE `llx_facture` ADD `date_relance` DATE NULL DEFAULT NULL; 
ALTER TABLE `llx_facture` ADD `relance_active` BOOLEAN NOT NULL DEFAULT TRUE; 

CREATE TABLE IF NOT EXISTS `llx_relance_clients` (
  `id` int(11) NOT NULL  AUTO_INCREMENT PRIMARY KEY,
  `id_user` int(11) NOT NULL DEFAULT 0,
  `mode` varchar(255) NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT current_timestamp,
  `pdf_file` varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `llx_relance_clients_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_relance` int(11) NOT NULL DEFAULT 0,
  `id_client` INT NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT 0,
  `relance_idx` int(11) NOT NULL DEFAULT 0,
  `id_contact` int(11) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL DEFAULT '',
  `factures` MEDIUMTEXT NOT NULL DEFAULT '',
  `pdf_file` VARCHAR(255) NOT NULL DEFAULT '',
  `date_send` DATETIME NULL,
  `id_user_send` INT NOT NULL DEFAULT '0'
);

INSERT INTO `llx_bimpcore_conf`(`name`, `value`) VALUES ('societe_default_outstanding_limit', 4000);
-- UPDATE `llx_facture` SET `nb_relance` = 0, `date_relance` = NULL WHERE 1;