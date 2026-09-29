-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: xtntrack
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `criteria_list`
--

DROP TABLE IF EXISTS `criteria_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `criteria_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `criteria` text NOT NULL,
  `order_by` int(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=109945 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `criteria_list`
--

LOCK TABLES `criteria_list` WRITE;
/*!40000 ALTER TABLE `criteria_list` DISABLE KEYS */;
INSERT INTO `criteria_list` VALUES (1,'Training Proper',0),(109940,'Resource Speaker',1),(109941,'Training Coordinator/Secretariat',2),(109942,'Elegancy',3),(109943,'Nicely',4),(109944,'Hello Testing',5);
/*!40000 ALTER TABLE `criteria_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_list`
--

DROP TABLE IF EXISTS `question_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `academic_id` int(30) NOT NULL,
  `question` text NOT NULL,
  `order_by` int(30) NOT NULL,
  `criteria_id` int(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_list`
--

LOCK TABLES `question_list` WRITE;
/*!40000 ALTER TABLE `question_list` DISABLE KEYS */;
INSERT INTO `question_list` VALUES (1,3,'Sample Question',0,1),(3,3,'Test',2,2),(5,0,'Question 101',0,1),(6,3,'Sample 101',4,1),(7,4,'Relevance Of the Training\r\n',0,1),(8,4,'hsjajsjs',1,109940),(9,4,'SPEAK NICELY ',2,1),(10,4,'BHCDNDNCNs',3,109940),(11,4,'hello testing',4,1);
/*!40000 ALTER TABLE `question_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_answers`
--

DROP TABLE IF EXISTS `evaluation_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_answers` (
  `evaluation_id` int(30) NOT NULL,
  `question_id` int(30) NOT NULL,
  `rate` int(20) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Evaluator_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_answers`
--

LOCK TABLES `evaluation_answers` WRITE;
/*!40000 ALTER TABLE `evaluation_answers` DISABLE KEYS */;
INSERT INTO `evaluation_answers` VALUES (1,1,5,0,1,''),(1,6,4,0,2,''),(1,3,5,0,3,''),(2,1,5,0,4,''),(2,6,5,0,5,''),(2,3,4,0,6,''),(3,1,5,0,7,''),(3,6,5,0,8,''),(3,3,4,0,9,''),(4,1,5,2,10,''),(4,5,5,2,11,''),(4,7,5,2,12,''),(4,6,5,2,13,''),(5,1,5,2,14,'kimmy'),(5,5,5,2,15,'kimmy'),(5,7,5,2,16,'kimmy'),(5,6,5,2,17,'kimmy'),(6,1,3,2,18,'swas'),(6,5,3,2,19,'swas'),(6,7,3,2,20,'swas'),(6,6,3,2,21,'swas'),(7,1,4,5,22,'MARRY ROSE'),(7,5,4,5,23,'MARRY ROSE'),(7,7,4,5,24,'MARRY ROSE'),(7,6,4,5,25,'MARRY ROSE');
/*!40000 ALTER TABLE `evaluation_answers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-29 22:31:46
