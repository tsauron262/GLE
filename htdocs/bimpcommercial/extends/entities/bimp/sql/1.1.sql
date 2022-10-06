ALTER TABLE `llx_propal` ADD ref_df_prolease VARCHAR(255) NOT NULL DEFAULT '';

ALTER TABLE `llx_propal` ADD devis_fin_status int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_propal` ADD contrat_fin_status int(11) NOT NULL DEFAULT 0;

ALTER TABLE `llx_propal` ADD id_signature_devis_fin int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_propal` ADD signature_df_params text NOT NULL DEFAULT '';
ALTER TABLE `llx_propal` ADD id_signature_contrat_fin int(11) NOT NULL DEFAULT 0;
ALTER TABLE `llx_propal` ADD signature_cf_params text NOT NULL DEFAULT '';