--
-- Table llx_olap_prod_cat_flat
-- Configuration de categorisation a plat
--
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
DROP TABLE IF EXISTS `llx_olap_prod_cat_flat`;
CREATE TABLE `llx_olap_prod_cat_flat` (
  `id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `label` varchar(32) NOT NULL,
  `lvl_max` int(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `path_UNIQUE` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `llx_olap_prod_cat_flat` VALUES (1,'Modèle','Modèle',2),(2,'Marque > Nature','Marque',4),
                (3,'Gamme > Transport','Transport',2),(4,'Gamme > Consommable','Consommable',2),
                (5,'Gamme > Matériel > Type','Matériel Type',6),(6,'Gamme > Matériel > Nature','Matériel Nature',4),
                (7,'Obsolescence > Nature','Obsolescence',3),(8,'Gamme > Logiciel > Type','Logiciel Type',4),
                (9,'Gamme > Logiciel > Nature','Logiciel Nature',3),(10,'Gamme > Service > Nature','Service Nature',4),
                (11,'Gamme > Service > Type','Service Type',4),(12,'Gamme > Service > Compétence','Service Compétence',5),
                (13,'Gamme > Textile > Type','Textile',4),(14,'Gamme > Autre > Nature','Autre',4),
                (15,'Recurrence > Périodique > Nature','RecurrencePériodiqueNature',5),
                (16,'Recurrence > Périodique > Type','RecurrencePériodiqueType',4),
                (17,'_Gamme','Gamme',1),(18,'_Recurrence','Recurrence',1);
COMMIT;
