ALTER TABLE `llx_bimp_propal_line` 
    ADD `abo_fac_periodicity` int(11) NOT NULL DEFAULT 0,
    ADD `abo_fac_term` tinyint(1) NOT NULL DEFAULT 1,
    ADD `abo_duration` int(11) NOT NULL DEFAULT 0,
    ADD `abo_nb_renouv` int(11) NOT NULL DEFAULT 0;