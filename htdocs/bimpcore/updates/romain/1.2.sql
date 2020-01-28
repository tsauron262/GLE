-- Add field used by BimpRemoveDuplicateV2
ALTER TABLE `llx_societe` ADD `duplicate` INT NOT NULL DEFAULT '0' AFTER `fk_entrepot`;
