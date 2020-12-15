ALTER TABLE `llx_buc_filters_config` ADD INDEX `owner` (`owner_type`, `id_owner`);
ALTER TABLE `llx_buc_filters_config` ADD INDEX `creator` (`id_user_create`);
ALTER TABLE `llx_buc_filters_config` ADD INDEX `obj_data` (`obj_module`, `obj_name`);

ALTER TABLE `llx_buc_list_filters` ADD INDEX `owner` (`owner_type`, `id_owner`);
ALTER TABLE `llx_buc_list_filters` ADD INDEX `creator` (`id_user_create`);
ALTER TABLE `llx_buc_list_filters` ADD INDEX `obj_data` (`obj_module`, `obj_name`);

ALTER TABLE `llx_buc_list_table_config` ADD INDEX `owner` (`owner_type`, `id_owner`);
ALTER TABLE `llx_buc_list_table_config` ADD INDEX `component` (`obj_module`, `obj_name`, `component_name`);
ALTER TABLE `llx_buc_list_table_config` ADD INDEX `creator` (`id_user_create`);

ALTER TABLE `llx_buc_stats_list_config` ADD INDEX `owner` (`owner_type`, `id_owner`);
ALTER TABLE `llx_buc_stats_list_config` ADD INDEX `component` (`obj_module`, `obj_name`, `component_name`);
ALTER TABLE `llx_buc_stats_list_config` ADD INDEX `creator` (`id_user_create`);

ALTER TABLE `llx_buc_user_current_config` ADD INDEX `user_curren` (`config_object_name`, `config_type_key`, `id_user`);

ALTER TABLE `llx_bimpcore_log` ADD INDEX `obj_data` (`obj_module`, `obj_name`, `id_object`);

ALTER TABLE `llx_bs_sav_issue` ADD INDEX `sav` (`id_sav`);

ALTER TABLE `llx_bds_process_cron` ADD INDEX `process` (`id_process`);
ALTER TABLE `llx_bds_process_cron` ADD INDEX `cron` (`id_cronjob`);
ALTER TABLE `llx_bds_process_cron` ADD INDEX `operation` (`id_operation`);

ALTER TABLE `llx_bds_process_cron_option` ADD INDEX `cron` (`id_cron`);
ALTER TABLE `llx_bds_process_cron_option` ADD INDEX `option` (`id_option`);

ALTER TABLE `llx_bds_process_match` ADD INDEX `process` (`id_process`);
ALTER TABLE `llx_bds_process_match_custom_values` ADD INDEX `match` (`id_match`);

ALTER TABLE `llx_bds_process_operation` ADD INDEX `process` (`id_process`);
ALTER TABLE `llx_bds_process_param` ADD INDEX `process` (`id_process`);
ALTER TABLE `llx_bds_process_trigger` ADD INDEX `process` (`id_process`);

ALTER TABLE `llx_bds_report` ADD INDEX `process` (`id_process`);
ALTER TABLE `llx_bds_report` ADD INDEX `operation` (`id_operation`);

ALTER TABLE `llx_bds_report_line` ADD INDEX `report` (`id_report`);
ALTER TABLE `llx_bds_report_line` ADD INDEX `obj_data` (`obj_module`, `obj_name`, `id_obj`);

ALTER TABLE `llx_bds_report_object_data` ADD INDEX `report` (`id_report`);
ALTER TABLE `llx_bds_report_object_data` ADD INDEX `obj_data` (`obj_module`, `obj_name`);

ALTER TABLE `llx_bimp_relance_clients_line` ADD INDEX `relance` (`id_relance`);
ALTER TABLE `llx_bimp_relance_clients_line` ADD INDEX `client` (`id_client`);