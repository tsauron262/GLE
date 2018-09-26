
ALTER TABLE `llx_bf_demande` ADD `vr_vente` FLOAT(10,2) NOT NULL AFTER `vr`;

ALTER TABLE `llx_bf_rent` ADD `periode2` BOOLEAN NOT NULL;

ALTER TABLE `llx_bimp_gsx_repair` ADD `canceled` BOOLEAN DEFAULT '0';