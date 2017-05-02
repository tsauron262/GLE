<?php

$tabsql = array();

$tabSql[] = "ALTER TABLE `".MAIN_DB_PREFIX."synopsiscaldav_event` ADD `participentExt` VARCHAR(1000) NOT NULL;";


$tabSql[] = "CREATE OR REPLACE VIEW calendarobjects AS (SELECT e.`id`, etag, uri, agendaplus, participentExt, IF(ar.fk_element, ar.fk_element, e.`fk_user_action`) as calendarid, '3715' as size, UNIX_TIMESTAMP(e.`tms`) as lastmodified, 'VEVENT' as componenttype, UNIX_TIMESTAMP(e.`datep`) as firstoccurence, UNIX_TIMESTAMP(e.`datep2`) as lastoccurence FROM `" . MAIN_DB_PREFIX . "actioncomm` e LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_resources ar ON ar.fk_actioncomm = e.id AND ar.element_type = 'user', " . MAIN_DB_PREFIX . "synopsiscaldav_event WHERE fk_object = e.id AND e.`datep2` + INTERVAL 3 MONTH > now()  AND fk_action NOT IN (3,8,9,10,30,31,40));";