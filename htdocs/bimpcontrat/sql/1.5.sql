ALTER TABLE `llx_contratdet` ADD `date_debut_validite` DATE DEFAULT NULL AFTER `date_ouverture`;
UPDATE `llx_contratdet` SET date_debut_validite = date_ouverture;