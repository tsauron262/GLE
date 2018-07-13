-- MySQL dump 10.13  Distrib 5.7.22, for Linux (x86_64)
--
-- Host: localhost    Database: test
-- ------------------------------------------------------
-- Server version	5.7.22-0ubuntu18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `test`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `test` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `test`;

--
-- Table structure for table `attribute`
--

DROP TABLE IF EXISTS `attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attribute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `id_attribute_extern` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute`
--

LOCK TABLES `attribute` WRITE;
/*!40000 ALTER TABLE `attribute` DISABLE KEYS */;
/*!40000 ALTER TABLE `attribute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attribute_value`
--

DROP TABLE IF EXISTS `attribute_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attribute_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `id_attribute_parent` int(11) NOT NULL,
  `id_attribute_value_extern` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute_value`
--

LOCK TABLES `attribute_value` WRITE;
/*!40000 ALTER TABLE `attribute_value` DISABLE KEYS */;
/*!40000 ALTER TABLE `attribute_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attribute_value_tariff`
--

DROP TABLE IF EXISTS `attribute_value_tariff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attribute_value_tariff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_tariff` int(11) NOT NULL,
  `fk_attribute_value` int(11) NOT NULL,
  `price` float DEFAULT NULL,
  `number-place` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tariff` (`fk_tariff`),
  KEY `fk_attribute_value` (`fk_attribute_value`),
  CONSTRAINT `attribute_value_tariff_ibfk_1` FOREIGN KEY (`fk_tariff`) REFERENCES `tariff` (`id`),
  CONSTRAINT `attribute_value_tariff_ibfk_2` FOREIGN KEY (`fk_attribute_value`) REFERENCES `attribute_value` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute_value_tariff`
--

LOCK TABLES `attribute_value_tariff` WRITE;
/*!40000 ALTER TABLE `attribute_value_tariff` DISABLE KEYS */;
/*!40000 ALTER TABLE `attribute_value_tariff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_start` datetime NOT NULL,
  `date_end` datetime NOT NULL,
  `description` text,
  `status` int(11) NOT NULL DEFAULT '0',
  `id_categ` int(11) DEFAULT NULL,
  `id_categ_parent` int(11) NOT NULL,
  `place` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_admin`
--

DROP TABLE IF EXISTS `event_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_event` int(11) NOT NULL,
  `fk_user` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_event` (`fk_event`),
  KEY `fk_user` (`fk_user`),
  CONSTRAINT `event_admin_ibfk_1` FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`),
  CONSTRAINT `event_admin_ibfk_2` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_admin`
--

LOCK TABLES `event_admin` WRITE;
/*!40000 ALTER TABLE `event_admin` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tariff`
--

DROP TABLE IF EXISTS `tariff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tariff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `date_creation` datetime DEFAULT NULL,
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `require_names` tinyint(1) NOT NULL DEFAULT '0',
  `price` float NOT NULL,
  `type_extra_1` int(11) DEFAULT NULL,
  `type_extra_2` int(11) DEFAULT NULL,
  `type_extra_3` int(11) DEFAULT NULL,
  `type_extra_4` int(11) DEFAULT NULL,
  `type_extra_5` int(11) DEFAULT NULL,
  `type_extra_6` int(11) DEFAULT NULL,
  `name_extra_1` varchar(255) DEFAULT NULL,
  `name_extra_2` varchar(255) DEFAULT NULL,
  `name_extra_3` varchar(255) DEFAULT NULL,
  `name_extra_4` varchar(255) DEFAULT NULL,
  `name_extra_5` varchar(255) DEFAULT NULL,
  `name_extra_6` varchar(255) DEFAULT NULL,
  `require_extra_1` int(11) DEFAULT NULL,
  `require_extra_2` int(11) DEFAULT NULL,
  `require_extra_3` int(11) DEFAULT NULL,
  `require_extra_4` int(11) DEFAULT NULL,
  `require_extra_5` int(11) DEFAULT NULL,
  `require_extra_6` int(11) DEFAULT NULL,
  `id_prod_extern` int(11) DEFAULT NULL,
  `fk_event` int(11) NOT NULL,
  `number_place` int(11) DEFAULT '0',
  `date_stop_sale` datetime DEFAULT NULL,
  `email_text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_event` (`fk_event`),
  CONSTRAINT `tariff_ibfk_1` FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tariff`
--

LOCK TABLES `tariff` WRITE;
/*!40000 ALTER TABLE `tariff` DISABLE KEYS */;
/*!40000 ALTER TABLE `tariff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tariff_attribute`
--

DROP TABLE IF EXISTS `tariff_attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tariff_attribute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_tariff` int(11) NOT NULL,
  `fk_attribute` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tariff` (`fk_tariff`),
  KEY `fk_attribute` (`fk_attribute`),
  CONSTRAINT `tariff_attribute_ibfk_1` FOREIGN KEY (`fk_tariff`) REFERENCES `tariff` (`id`),
  CONSTRAINT `tariff_attribute_ibfk_2` FOREIGN KEY (`fk_attribute`) REFERENCES `attribute` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tariff_attribute`
--

LOCK TABLES `tariff_attribute` WRITE;
/*!40000 ALTER TABLE `tariff_attribute` DISABLE KEYS */;
/*!40000 ALTER TABLE `tariff_attribute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket`
--

DROP TABLE IF EXISTS `ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_creation` datetime DEFAULT NULL,
  `fk_event` int(11) NOT NULL,
  `fk_tariff` int(11) NOT NULL,
  `fk_user` int(11) NOT NULL,
  `date_scan` datetime DEFAULT NULL,
  `barcode` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `price` float DEFAULT NULL,
  `extra_1` text,
  `extra_2` text,
  `extra_3` text,
  `extra_4` text,
  `extra_5` text,
  `extra_6` text,
  `id_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `fk_event` (`fk_event`),
  KEY `fk_tariff` (`fk_tariff`),
  KEY `fk_user` (`fk_user`),
  CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`fk_event`) REFERENCES `event` (`id`),
  CONSTRAINT `ticket_ibfk_2` FOREIGN KEY (`fk_tariff`) REFERENCES `tariff` (`id`),
  CONSTRAINT `ticket_ibfk_3` FOREIGN KEY (`fk_user`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket`
--

LOCK TABLES `ticket` WRITE;
/*!40000 ALTER TABLE `ticket` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `pass_word` varchar(255) NOT NULL,
  `status` int(11) DEFAULT '0',
  `create_event_tariff` int(11) DEFAULT '0',
  `reserve_ticket` int(11) DEFAULT '0',
  `validate_event` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'admin','admin','admin','root','toor',2,0,0,0),(2,'prestashop','prestashop','prestashop','prestashop','C0SV6UQumTADcq4EGgMsBviFM27oBJ6P',1,0,0,0);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-07-10 10:17:39
