CREATE TABLE IF NOT EXISTS `llx_bimp_objects_associations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `src_object_module` VARCHAR(128) NOT NULL,
  `src_object_name` varchar(128) NOT NULL,
  `src_object_type` varchar(128) NOT NULL,
  `src_id_object` int(10) UNSIGNED NOT NULL,
  `dest_object_module` VARCHAR(128) NOT NULL,
  `dest_object_name` varchar(128) NOT NULL,
  `dest_object_type` varchar(128) NOT NULL,
  `dest_id_object` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;