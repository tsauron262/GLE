CREATE TABLE IF NOT EXISTS `llx_bl_shipment_line` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_shipment` int(11) unsigned NOT NULL DEFAULT 0,
  `id_commande_line` int(11) unsigned NOT NULL DEFAULT 0,
  `id_entrepot_dest` int(11) unsigned NOT NULL DEFAULT 0,
  `qty` double(24,8) NOT NULL DEFAULT 0.00000000
  KEY `shipment` (`id_shipment`),
  KEY `commande_line` (`id_commande_line`)
)