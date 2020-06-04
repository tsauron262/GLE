INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES ('contrat', 'external', 'SITE', 'Site d''intervention', '1', NULL, '0');

UPDATE `llx_c_type_contact` SET `libelle` = "Site d'intervention" WHERE `llx_c_type_contact`.`code` = 'SITE'; 