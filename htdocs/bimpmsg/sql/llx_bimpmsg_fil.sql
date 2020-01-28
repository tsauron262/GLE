CREATE TABLE IF NOT EXISTS llx_bimpmsg_fil (
  `id` int(11) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
  `fk_author` int(11) DEFAULT NULL,
  `user_create` int(11) NOT NULL,
  `user_update` int(11) DEFAULT NULL,
  `date_create` datetime NOT NULL DEFAULT current_timestamp,
  `date_update` datetime NOT NULL
);