
ALTER TABLE `llx_bf_demande` CHANGE `commission_commerciale` `commission_commerciale` DECIMAL(24,6) NOT NULL DEFAULT '0';
ALTER TABLE `llx_bf_demande` CHANGE `commission_financiere` `commission_financiere` DECIMAL(24,6) NOT NULL DEFAULT '0';

ALTER TABLE `llx_bf_refinanceur` ADD `coefs` TEXT NOT NULL DEFAULT '' AFTER `id_societe`;