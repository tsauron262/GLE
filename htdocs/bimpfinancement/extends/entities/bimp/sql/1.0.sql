ALTER TABLE `llx_bf_demande`ADD `id_bimp_propale` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande`ADD `id_bimp_client` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande`ADD `id_bimp_contact` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande`ADD `id_bimp_commercial` int(11) NOT NULL DEFAULT 0;

ALTER TABLE `llx_bf_demande`ADD `bimp_propale` longtext NOT NULL DEFAULT '';
ALTER TABLE `llx_bf_demande`ADD `bimp_client` longtext NOT NULL DEFAULT '';
ALTER TABLE `llx_bf_demande`ADD `bimp_contact` longtext NOT NULL DEFAULT '';
ALTER TABLE `llx_bf_demande`ADD `bimp_commercial` longtext NOT NULL DEFAULT '';

ALTER TABLE `llx_bf_demande_line`ADD `id_bimp_propale_line` int(11) NOT NULL DEFAULT 0;