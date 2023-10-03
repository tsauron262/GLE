CREATE TABLE IF NOT EXISTS `llx_bimpcore_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `src_module` varchar(255) NOT NULL DEFAULT '',
  `src_name` varchar(255) NOT NULL DEFAULT '',
  `src_id` int(11) NOT NULL DEFAULT 0,
  `src_field` varchar(255) NOT NULL DEFAULT '',
  `linked_type` varchar(10) NOT NULL DEFAULT '',
  `linked_module` varchar(255) NOT NULL DEFAULT '',
  `linked_name` varchar(255) NOT NULL DEFAULT '',
  `linked_file` varchar(255) NOT NULL DEFAULT '',
  `linked_id` int(11) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS `llx_bimpcore_hashtag` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  `description` TEXT NOT NULL
);