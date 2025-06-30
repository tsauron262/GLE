ALTER TABLE `llx_bimpcore_log` ADD `hash` varchar(255) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS `llx_bimpcore_log_datetime` (
  `id_log` int(11) NOT NULL DEFAULT 0,
  `datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);
