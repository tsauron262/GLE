

--
-- Structure de la table `llx_synopsisapple_shipment`
--

CREATE TABLE IF NOT EXISTS `llx_synopsisapple_shipment` (
  `rowid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ship_to` text NOT NULL,
  `length` int(11) NOT NULL DEFAULT '0',
  `width` int(11) NOT NULL DEFAULT '0',
  `height` int(11) NOT NULL DEFAULT '0',
  `weight` int(11) NOT NULL DEFAULT '0',
  `transportation_charges` decimal(10,0) NOT NULL DEFAULT '0',
  `options_charges` decimal(10,0) NOT NULL DEFAULT '0',
  `total_charges` decimal(10,0) NOT NULL DEFAULT '0',
  `billing_weight` decimal(10,0) NOT NULL DEFAULT '0',
  `tracking_number` text,
  `identification_number` text,
  `gsx_confirmation` text,
  `gsx_return_id` text,
  `gsx_pdf_name` text,
  `gsx_tracking_url` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=28 ;

--
-- Structure de la table `llx_synopsisapple_shipment_parts`
--

CREATE TABLE IF NOT EXISTS `llx_synopsisapple_shipment_parts` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `part_number` text NOT NULL,
  `part_new_number` text,
  `part_po_number` text NOT NULL,
  `repair_number` text NOT NULL,
  `serial` text NOT NULL,
  `return_order_number` text NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=31 ;