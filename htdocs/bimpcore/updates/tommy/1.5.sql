CREATE TABLE `llx_bimp_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `src` varchar(127) NOT NULL,
  `dst` varchar(127) NOT NULL,
  `subj` varchar(254) NOT NULL,
  `txt` text NOT NULL,
  `comment` text DEFAULT NULL,
  `id_user_owner` int(11) DEFAULT NULL,
  `user_create` int(11) NOT NULL,
  `user_update` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT 0,
  `prio` int(11) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT current_timestamp,
  `date_update` datetime NOT NULL,
  test_ferme text
) ;

ALTER TABLE `llx_bimp_task` ADD PRIMARY KEY(`id`);
