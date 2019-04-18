CREATE TABLE IF NOT EXISTS `llx_bcontract_prelevement` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `id_contrat` int(11) NOT NULL,
  `next_facture_date` datetime NOT NULL,
  `next_facture_amount` double NOT NULL,
  `validate` int(1) DEFAULT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT "0",
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT "0",
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;