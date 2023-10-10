ALTER TABLE `llx_bimpcore_signature` ADD `status` INT(11) NOT NULL DEFAULT 0 AFTER `doc_type`;
ALTER TABLE `llx_bimpcore_signature` ADD `obj_params_field` VARCHAR(30) NOT NULL DEFAULT '' AFTER `id_obj`;

UPDATE `llx_bimpcore_signature` SET `status` = 10 WHERE (`signed` = 1 OR `type` > 0);
UPDATE `llx_bimpcore_signature` SET `status` = -1 WHERE `type` = -1;
UPDATE `llx_bimpcore_signature` SET `status` = -2 WHERE `type` = -2;
UPDATE `llx_bimpcore_signature` SET `type` = 0 WHERE `type` < 0;

UPDATE `llx_bimpcore_signature` SET `obj_params_field` = 'signature_params' WHERE `obj_name` IN ('Bimp_Propal','BS_SavPropal','BContract_contrat','BL_CommandeShipment');
UPDATE `llx_bimpcore_signature` SET `obj_params_field` = 'signature_df_params' WHERE `obj_name` = 'BimpCommDemandeFin' AND `doc_type` = 'devis_fin';
UPDATE `llx_bimpcore_signature` SET `obj_params_field` = 'signature_cf_params' WHERE `obj_name` = 'BimpCommDemandeFin' AND `doc_type` = 'contrat_fin';
UPDATE `llx_bimpcore_signature` SET `obj_params_field` = 'signature_devis_params' WHERE `obj_name` = 'BF_Demande' AND `doc_type` = 'devis';
UPDATE `llx_bimpcore_signature` SET `obj_params_field` = 'signature_contrat_params' WHERE `obj_name` = 'BF_Demande' AND `doc_type` = 'contrat';
UPDATE `llx_bimpcore_signature` SET `obj_params_field` = 'signature_resti_params' WHERE `obj_name` = 'BS_SAV' AND `doc_type` = 'sav_resti';
UPDATE `llx_bimpcore_signature` SET `obj_params_field` = 'default_signature_pc_params' WHERE `obj_name` = 'BS_SAV' AND `doc_type` = 'sav_pc';

CREATE TABLE IF NOT EXISTS `llx_bimpcore_signature_signataire` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_signature` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `status` int(11) NOT NULL DEFAULT 0,
  `type_signature` int(11) NOT NULL DEFAULT 0,
  `code` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT 'Signataire',
  `id_client` int(11) NOT NULL DEFAULT 0,
  `id_contact` int(11) NOT NULL DEFAULT 0,
  `id_user_client_signataire` int(11) NOT NULL DEFAULT 0,
  `id_user` int(11) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL DEFAULT '',
  `nom` varchar(255) NOT NULL DEFAULT '',
  `fonction` varchar(255) NOT NULL DEFAULT '',
  `date_open` date DEFAULT NULL,
  `date_signed` datetime DEFAULT NULL,
  `allowed_users_client` TEXT NOT NULL,
  `ip_signataire` varchar(255) NOT NULL DEFAULT '',
  `base_64_signature` mediumtext DEFAULT NULL,
  `allow_elec` tinyint(1) NOT NULL DEFAULT 1,
  `allow_dist` tinyint(1) NOT NULL DEFAULT 0,
  `allow_docusign` tinyint(1) NOT NULL DEFAULT 0,
  `allow_refuse` tinyint(1) NOT NULL DEFAULT 0,
  `need_sms_code` tinyint(1) NOT NULL DEFAULT 0,
  `code_sms_infos` TEXT NOT NULL,
  KEY `id_signature` (`id_signature`)
);