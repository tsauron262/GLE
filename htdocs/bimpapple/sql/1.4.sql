CREATE TABLE `llx_bimp_apple_consigned_stock` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `part_number` varchar(255) NOT NULL DEFAULT '',
  `qty` int(11) NOT NULL DEFAULT 0,
  `code_centre` varchar(5) NOT NULL DEFAULT '',
  `serials` text NOT NULL
);