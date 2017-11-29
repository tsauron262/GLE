ALTER TABLE `llx_bf_demande` CHANGE `terme_paiement` `mode_calcul` INT(11) NOT NULL DEFAULT '2';

ALTER TABLE `llx_bf_frais_fournisseur` ADD `paid` BOOLEAN NOT NULL DEFAULT FALSE AFTER `amount`;

ALTER TABLE `llx_bf_rent` ADD  `position` int(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `payment`;