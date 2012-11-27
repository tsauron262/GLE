CREATE TABLE `llx_Synopsis_Dashboard` (
  `id` int(11) NOT NULL auto_increment,
  `params` longtext,
  `user_refid` int(11) default NULL,
  `dash_type_refid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `user_refid` (`user_refid`),
  KEY `dash_type_refid` (`dash_type_refid`)
) ENGINE=InnoDB AUTO_INCREMENT=1642 DEFAULT CHARSET=latin1;
