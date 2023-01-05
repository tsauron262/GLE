ALTER TABLE `llx_bcontract_prelevement` ADD `statut` INT(11) default "1";
ALTER TABLE `llx_bcontract_prelevement` ADD `commercial` INT(11) NOT NULL DEFAULT "0";
ALTER TABLE `llx_bcontract_prelevement`ADD `client` INT(11) NOT NULL DEFAULT "0";
