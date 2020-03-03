ALTER TABLE `llx_synopsisfichinter_extra_value` ADD INDEX( `interv_refid`, `extra_key_refid`, `typeI`);
ALTER TABLE `llx_synopsisfichinter_extra_values_choice` ADD INDEX( `value`, `key_refid`);