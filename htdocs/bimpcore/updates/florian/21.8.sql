ALTER TABLE `llx_bimpcore_log` ADD INDEX `obj_data` (`obj_module`, `obj_name`, `id_object`);


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
