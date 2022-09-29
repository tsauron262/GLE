ALTER TABLE `llx_bf_demande`ADD `ext_origine` VARCHAR(20) NOT NULL DEFAULT 'none';

ALTER TABLE `llx_bf_demande`ADD `id_ext_propale` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande`ADD `id_ext_client` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande`ADD `id_ext_contact` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_bf_demande`ADD `id_ext_commercial` int(11) NOT NULL DEFAULT 0;

ALTER TABLE `llx_bf_demande`ADD `ext_propale` longtext NOT NULL DEFAULT '';
ALTER TABLE `llx_bf_demande`ADD `ext_client` longtext NOT NULL DEFAULT '';
ALTER TABLE `llx_bf_demande`ADD `ext_contact` longtext NOT NULL DEFAULT '';
ALTER TABLE `llx_bf_demande`ADD `ext_commercial` longtext NOT NULL DEFAULT '';

ALTER TABLE `llx_bf_demande_line`ADD `id_ext_propale_line` int(11) NOT NULL DEFAULT 0;