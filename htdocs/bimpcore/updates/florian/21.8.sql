ALTER TABLE `llx_bimpcore_log` ADD INDEX `obj_data` (`obj_module`, `obj_name`, `id_object`);


ALTER TABLE `llx_bimp_relance_clients_line` ADD INDEX `relance` (`id_relance`);
ALTER TABLE `llx_bimp_relance_clients_line` ADD INDEX `client` (`id_client`);
