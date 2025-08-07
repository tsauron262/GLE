ALTER TABLE llx_bimp_notification ADD active INT DEFAULT 1 NULL;
INSERT INTO `llx_bimp_notification` (`label`, `nom`, `module`, `class`, `method`, `user_create`, `user_update`, `active`) VALUES ('Événements', 'actioncomm_event', 'bimpcore', 'Bimp_ActionComm', 'getActionCommEventsForUser', 1, 1, 0);
ALTER TABLE `llx_c_actioncomm` ADD `user_notif` tinyint(1) NOT NULL DEFAULT 0;
UPDATE `llx_c_actioncomm` SET `user_notif` = 1 WHERE `code` IN('AC_TEL', 'AC_FAX', 'AC_EMAIL', 'AC_RDV', 'AC_INT', 'RDV_EXT', 'RDV_INT', 'ATELIER', 'LIV', 'INTER', 'INTER_SG', 'FORM_INT', 'FORM_EXT', 'FORM_CERTIF', 'VIS_CTR', 'TELE', 'JPO', 'HOT', 'AV_VENTE', 'REU_INT', 'TACHE', 'DEP_EXT');
