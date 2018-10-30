
ALTER TABLE `llx_bf_demande_line` ADD `label` VARCHAR(256) NOT NULL DEFAULT '' AFTER `type`;
ALTER TABLE `llx_bf_demande_line` CHANGE `tva_tx` `tva_tx` DECIMAL(8,2) NOT NULL;