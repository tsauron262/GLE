UPDATE `llx_c_type_contact` SET `libelle` = 'Adresse de facturation' WHERE `llx_c_type_contact`.`code` = 'BILLING';

INSERT INTO `llx_c_type_contact`(`element`, `source`, `code`, `libelle`, `active`, `module`) VALUES ('propal', 'external', 'BILLING2', 'Contact facturation EMail', 1, 'bimpcommercial');
INSERT INTO `llx_c_type_contact`(`element`, `source`, `code`, `libelle`, `active`, `module`) VALUES ('commande', 'external', 'BILLING2', 'Contact facturation EMail', 1, 'bimpcommercial');
INSERT INTO `llx_c_type_contact`(`element`, `source`, `code`, `libelle`, `active`, `module`) VALUES ('facture', 'external', 'BILLING2', 'Contact facturation EMail', 1, 'bimpcommercial');