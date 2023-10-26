ALTER TABLE `llx_contrat` 
ADD `fk_user_validate` int(11) DEFAULT NULL AFTER `fk_user_author`,
ADD `date_validate` DATETIME DEFAULT NULL AFTER `datec`;

ALTER TABLE `llx_contratdet` 
    ADD `line_type` int(11) NOT NULL DEFAULT 0,
    ADD `fac_periodicity` int(11) NOT NULL DEFAULT 0,
    ADD `fac_term` tinyint(1) NOT NULL DEFAULT 1, 
    ADD `achat_periodicity` int(11) NOT NULL DEFAULT 0,
    ADD `variable_qty` tinyint(1) NOT NULL DEFAULT 0, 
    ADD `duration` int(11) NOT NULL DEFAULT 0,
    ADD `nb_renouv` int(11) NOT NULL DEFAULT 0,
    ADD `date_next_achat` DATE DEFAULT NULL AFTER `date_cloture`,
    ADD `date_next_facture` DATE DEFAULT NULL AFTER `date_cloture`;