ALTER TABLE `llx_bimpcore_conf` ADD `module` VARCHAR(30) NOT NULL DEFAULT 'bimpcore' AFTER `id`;

UPDATE `llx_bimpcore_conf` SET `name` = 'id_default_bank_account', `module` = 'bimpcore' WHERE `name` = 'bimpcaisse_id_default_account' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'id_default_tva_tx', `module` = 'bimpcore' WHERE `name` = 'tva_default' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_products_cur_pa_history', `module` = 'bimpcore' WHERE `name` = 'use_new_cur_pa_method' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'check_versions_lock', `module` = 'bimpcore' WHERE `name` = 'bimp_core_check_versions_lock' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_bimp_logs', `module` = 'bimpcore' WHERE `name` = 'bimpcore_use_logs' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'mode_eco', `module` = 'bimpcore' WHERE `name` = 'bimpcore_mode_eco' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_bimp_object_instances_clones', `module` = 'bimpcore' WHERE `name` = 'bimpcore_use_bimp_object_instances_clones' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'print_bimp_logs', `module` = 'bimpcore' WHERE `name` = 'bimpcore_print_logs' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'bimp_log_urgent_send_email', `module` = 'bimpcore' WHERE `name` = 'bimpcore_logs_urgents_send_email' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_db_transactions', `module` = 'bimpcore' WHERE `name` = 'bimpcore_use_db_transactions' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'cache_server_classname', `module` = 'bimpcore' WHERE `name` = 'bimpcore_cache_server_classname' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'log_actions_rollbacks', `module` = 'bimpcore' WHERE `name` = 'bimpcore_log_actions_rollbacks' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'pdf_use_html_purifier', `module` = 'bimpcore' WHERE `name` = 'bimpcore_pdf_use_html_purifier' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_cache_for_cards', `module` = 'bimpcore' WHERE `name` = 'bimpcore_user_cache_for_cards' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'default_id_entrepot', `module` = 'bimpcore' WHERE `name` = 'default_entrepot' AND `module` = 'bimpcore';

UPDATE `llx_bimpcore_conf` SET `name` = 'pdf_add_cgv', `module` = 'bimpcommercial' WHERE `name` = 'CGV_BIMP' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_tx_marque', `module` = 'bimpcommercial' WHERE `name` = 'bimpcomm_tx_marque' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_relances_paiements_clients', `module` = 'bimpcommercial' WHERE `name` = 'USE_RELANCE' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_marge_in_parent_bimpcomm', `module` = 'bimpcommercial' WHERE `name` = 'USE_MARGE_IN_PARENT_BIMPCOMM' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_statut_export', `module` = 'bimpcommercial' WHERE `name` = 'USE_STATUT_EXPORT' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'relance_paiements_globale_date_lim', `module` = 'bimpcommercial' WHERE `name` = 'relance_paiements_globale_date_lim' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'relance_paiements_id_bank_account', `module` = 'bimpcommercial' WHERE `name` = 'relance_paiements_id_bank_account' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'relance_paiements_extented_delay_type_ent', `module` = 'bimpcommercial' WHERE `name` = 'relance_paiements_extented_delay_type_ent' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'relance_paiements_globale_excluded_modes_reglement', `module` = 'bimpcommercial' WHERE `name` = 'relance_paiements_globale_excluded_modes_reglement' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'email_for_relances_deactivated_notification', `module` = 'bimpcommercial' WHERE `name` = 'email_for_relances_deactivated_notification' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'date_debut_exercice', `module` = 'bimpcommercial' WHERE `name` = 'date_debut_exercice' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'propal_use_signatures', `module` = 'bimpcommercial' WHERE `name` = 'bimp_propal_use_signatures' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'commande_required_for_factures', `module` = 'bimpcommercial' WHERE `name` = 'force_use_commande' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'solvabilite_validation_emails', `module` = 'bimpcommercial' WHERE `name` = 'bimpcomm_solvabilite_validation_emails' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'relances_clients_entrepots_speciaux', `module` = 'bimpcommercial' WHERE `name` = 'bimpcore_relances_clients_entrepots_speciaux' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'max_rtp_for_classify_paid', `module` = 'bimpcommercial' WHERE `name` = 'bimpcommercial_max_rtp_for_classify_paid' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'propal_pdf_chorus_mention', `module` = 'bimpcommercial' WHERE `name` = 'propal_pdf_chorus_mention' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'new_bank_account_date', `module` = 'bimpcommercial' WHERE `name` = 'new_bank_account_date' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'typent_required', `module` = 'bimpcommercial' WHERE `name` = 'fk_typent_REQUIRED' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'id_user_mail_comm_line_expire', `module` = 'bimpcommercial' WHERE `name` = 'id_user_mail_comm_line_expire' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'tva_tx_for_acomptes', `module` = 'bimpcommercial' WHERE `name` = 'tva_acompte' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'user_as_default_commercial', `module` = 'bimpcommercial' WHERE `name` = 'user_as_default_commercial' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'contact_facturation_required_for_commandes', `module` = 'bimpcommercial', `value` = '0' WHERE `name` = 'NOT_FORCE_CONTACT' AND `module` = 'bimpcore' AND `value` = '1';
UPDATE `llx_bimpcore_conf` SET `name` = 'contact_facturation_required_for_commandes', `module` = 'bimpcommercial', `value` = '1' WHERE `name` = 'NOT_FORCE_C`ONTACT' AND `module` = 'bimpcore' AND `value` = '0';
UPDATE `llx_bimpcore_conf` SET `name` = 'default_relance_paiements_delay_days', `module` = 'bimpcommercial' WHERE `name` = 'relance_paiements_facture_delay_days' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'allow_pdf_regeneration_after_validation', `module` = 'bimpcommercial', `value` = '0' WHERE `name` = 'BLOQUE_GENERATE_FACT_PDF' AND `module` = 'bimpcore' AND `value` = '1';
UPDATE `llx_bimpcore_conf` SET `name` = 'allow_pdf_regeneration_after_validation', `module` = 'bimpcommercial', `value` = '1' WHERE `name` = 'BLOQUE_GENERATE_FACT_PDF' AND `module` = 'bimpcore' AND `value` = '0';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_free_objectline', `module` = 'bimpcommercial' WHERE `name` = 'use_freeline' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `value` = '1' WHERE `name` = 'use_free_objectline' AND `module` = 'bimpcommercial' AND `value` = '0' AND (SELECT COUNT(`id`) FROM `llx_bimpcore_conf` WHERE `name` = 'LINE_FREE_ACTIVE' AND `module` = 'bimpcore' AND `value` = 1) > '1';

UPDATE `llx_bimpcore_conf` SET `name` = 'email_groupe', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_email_groupe' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'email_facturation', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_email_facturation' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'pdf_use_signature', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_pdf_use_signature' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'pdf_title', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_pdf_title' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'pdf_signataire', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_pdf_signataire' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'pdf_signataire_function', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_pdf_signataire_function' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'autorised_service_codes', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_autorised_service_codes' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_autorised_service', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_use_autorised_service' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'just_code_service', `module` = 'bimpcontract' WHERE `name` = 'bimpcontract_just_code_service' AND `module` = 'bimpcore';

UPDATE `llx_bimpcore_conf` SET `name` = 'cout_horaire_technicien', `module` = 'bimptechnique' WHERE `name` = 'bimptechnique_coup_horaire_technicien' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'default_id_user_actioncomm', `module` = 'bimptechnique' WHERE `name` = 'bimptechnique_default_user_actionComm' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'ref_temps_passe', `module` = 'bimptechnique' WHERE `name` = 'bimptechnique_ref_temps_passe' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'id_societe_auto_terminer', `module` = 'bimptechnique' WHERE `name` = 'bimptechnique_id_societe_auto_terminer' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'id_serv19', `module` = 'bimptechnique' WHERE `name` = 'bimptechnique_id_serv19' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'id_dep', `module` = 'bimptechnique' WHERE `name` = 'bimptechnique_id_dep' AND `module` = 'bimpcore';

UPDATE `llx_bimpcore_conf` SET `name` = 'id_cond_reglement_default', `module` = 'bimpcaisse' WHERE `name` = 'bimpcaisse_id_cond_reglement_default' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'secteur_code', `module` = 'bimpcaisse' WHERE `name` = 'bimpcaisse_secteur_code' AND `module` = 'bimpcore';

UPDATE `llx_bimpcore_conf` SET `name` = 'sav_public_reservations', `module` = 'bimpsupport' WHERE `name` = 'sav_public_reservations' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'sav_mode_reglement', `module` = 'bimpsupport' WHERE `name` = 'sav_mode_reglement' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'sav_cond_reglement', `module` = 'bimpsupport' WHERE `name` = 'sav_cond_reglement' AND `module` = 'bimpcore';

UPDATE `llx_bimpcore_conf` SET `name` = 'use_gsx_v2', `module` = 'bimpapple' WHERE `name` = 'use_gsx_v2' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'use_gsx_v2_for_reservations', `module` = 'bimpapple' WHERE `name` = 'use_gsx_v2_for_reservations' AND `module` = 'bimpcore';

UPDATE `llx_bimpcore_conf` SET `name` = 'clean_reports_id_cronjob', `module` = 'bimpdatasync' WHERE `name` = 'bds_clean_reports_id_cronjob' AND `module` = 'bimpcore';
UPDATE `llx_bimpcore_conf` SET `name` = 'relances_clients_id_process', `module` = 'bimpdatasync' WHERE `name` = 'bds_relances_clients_id_process' AND `module` = 'bimpcore';

UPDATE `llx_bimpcore_conf` SET `name` = 'base_url', `module` = 'bimpinterfaceclient' WHERE `name` = 'interface_client_base_url' AND `module` = 'bimpcore';