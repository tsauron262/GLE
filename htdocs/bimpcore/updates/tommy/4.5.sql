CREATE TABLE `llx_bimpcore_alert` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) NOT NULL,
  `status` int(11) NOT NULL,
  `conditionExec` text NOT NULL,
  `execution` text NOT NULL,
  `niveau` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `msg` varchar(200) NOT NULL,
    PRIMARY KEY (`id`));