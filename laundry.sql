-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: laundry
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `clothing_items`
--

DROP TABLE IF EXISTS `clothing_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clothing_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clothing_items`
--

LOCK TABLES `clothing_items` WRITE;
/*!40000 ALTER TABLE `clothing_items` DISABLE KEYS */;
INSERT INTO `clothing_items` VALUES (1,'Shirt',5.00,'2025-04-24 09:00:00'),(2,'Pants',7.00,'2025-04-24 09:00:00'),(3,'Towel',3.00,'2025-04-24 09:00:00'),(4,'Jacket',10.00,'2025-04-24 09:00:00'),(5,'chupi',50.00,'2025-04-24 17:18:16');
/*!40000 ALTER TABLE `clothing_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reorder_level` int NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,'Detergent',50,'Liters',10,'2025-04-15 15:28:00'),(2,'Soap',5,'Bars',20,'2025-04-15 15:28:00');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tag_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `items` text COLLATE utf8mb4_general_ci,
  `status` enum('pending','in_progress','completed','delivered') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `drop_off_date` datetime NOT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `tag_number` (`tag_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,2,'TEMP-1','5 shirts, 2 pants','pending','2025-04-26 17:43:00',NULL,25.00,'2025-04-15 14:43:55'),(2,2,'TEMP-2','3 towels','in_progress','2025-04-25 12:00:00',NULL,15.00,'2025-04-15 14:44:00'),(3,2,'TEMP-3','1 jacket','completed','2025-04-24 09:00:00','2025-04-25 09:00:00',20.00,'2025-04-15 14:44:05'),(4,2,'TEMP-4','2 shirts','delivered','2025-04-23 14:00:00','2025-04-24 14:00:00',10.00,'2025-04-15 14:44:10'),(5,4,'TEMP-5','2 shirts ','pending','2025-04-24 19:25:00',NULL,0.00,'2025-04-24 16:25:05'),(6,4,'ORD-2025-0006','2 Shirt, 5 Pants, 1 Towel, 4 Jacket','completed','2025-04-25 19:52:00',NULL,88.00,'2025-04-24 16:52:58');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','mobile_money','bank_transfer','card') COLLATE utf8mb4_general_ci NOT NULL,
  `payment_status` enum('pending','completed','failed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,39.00,'cash','pending','2025-04-24 09:00:00'),(2,2,9.00,'mobile_money','pending','2025-04-24 09:00:00'),(3,3,10.00,'card','completed','2025-04-24 09:00:00'),(4,4,10.00,'bank_transfer','completed','2025-04-24 09:00:00'),(5,6,88.00,'cash','completed','2025-04-24 17:09:11');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reset_codes`
--

DROP TABLE IF EXISTS `reset_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reset_codes`
--

LOCK TABLES `reset_codes` WRITE;
/*!40000 ALTER TABLE `reset_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `reset_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('customer','staff','admin') COLLATE utf8mb4_general_ci NOT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'assets/images/default-profile.png' COMMENT 'Path to user profile picture image',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@admin.com','255715641115','$2y$10$960CPgM15hjGBZsev8z5y.VgtgkBYSEc1tlgcdGPfN0CiXchuXj7e','admin','assets/images/default-profile.png','2025-04-15 15:05:28'),(2,'John Doe','john@example.com','255123456789','$2y$10$LOvSEqUb3DmnB9..Y0FkSee81D49sOytrq0aLfjdWM/ra/h0KKwyO','customer','assets/images/default-profile.png','2025-04-15 14:43:15'),(3,'Jane Smith','jane@example.com','255987123456','$2y$10$uF9ISdLvlKwYrbHtcb0IAue6M8hb56CUAlknLCORFi2oVFgymse1u','staff','assets/images/default-profile.png','2025-04-15 15:17:20'),(4,'lucas','lucas@gmail.com','255715641114','$2y$10$5.i/XLNHIXAXpEuWbjnMK.NdxBs1vkL7VlXXauS2a/slYQqbW1gGG','customer','assets/images/profile_4_1752738089.jpg','2025-04-24 16:24:15'),(5,'eddo','eddo@gmail.com','255712345678','$2y$10$h7LsxpUYv0GyJ6wvcDngOupw1bUMn963Uueev2zpO2JvZ0URkwCnu','staff','assets/images/default-profile.png','2025-04-24 16:26:45');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-17 11:11:15
