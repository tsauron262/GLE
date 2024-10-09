CREATE TABLE IF NOT EXISTS `llx_bimp_user_element` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `element` varchar(1000) NOT NULL DEFAULT '0',
  `id_user` int(11) NOT NULL DEFAULT 0,
  `can_edit` int(11) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS `llx_bimp_usergroup_element` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `element` varchar(1000) NOT NULL DEFAULT '0',
  `id_usergroup` int(11) NOT NULL DEFAULT 0
);