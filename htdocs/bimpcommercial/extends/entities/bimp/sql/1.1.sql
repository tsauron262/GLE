ALTER TABLE `llx_bimpcomm_demande_fin` ADD id_contact_suivi int(11) NOT NULL DEFAULT 0 AFTER `status`;
ALTER TABLE `llx_bimpcomm_demande_fin` ADD id_contact_signature int(11) NOT NULL DEFAULT 0 AFTER `id_contact_suivi`;
ALTER TABLE `llx_bimpcomm_demande_fin` ADD contacts_livraisons text NOT NULL DEFAULT '' AFTER `id_contact_signature`;