/*
SQLyog Community v13.3.0 (64 bit)
MySQL - 10.4.32-MariaDB : Database - motorshop
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`motorshop` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `motorshop`;

/*Table structure for table `admin` */

DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `admin` */

insert  into `admin`(`id`,`username`,`password`) values 
(1,'admin','$2y$10$Snz7pYLZ1I9wdYRKPchBMO3feRpFRfHrEShlT8ZD6oJ0HFTXGNLAu');

/*Table structure for table `appointments` */

DROP TABLE IF EXISTS `appointments`;

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `service_description` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `mechanic_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `mechanic_id` (`mechanic_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `appointments` */

insert  into `appointments`(`id`,`user_id`,`vehicle_model`,`service_description`,`preferred_date`,`preferred_time`,`status`,`mechanic_id`,`admin_notes`,`customer_notes`,`created_at`,`updated_at`) values 
(1,7,'Honda Click 125','Oil Change','2025-06-04','15:40','Completed',1,NULL,'','2025-06-03 13:40:51','2025-06-03 18:28:43'),
(2,7,'Honda Click 125','General Repair','2025-06-12','18:52','Completed',4,'','','2025-06-03 14:52:29','2025-06-03 18:36:51'),
(5,9,'Mio i125','Oil Change','2025-06-09','06:06','Pending',4,NULL,'Add gear oil','2025-06-03 18:07:07','2025-06-03 18:27:09');

/*Table structure for table `categories` */

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `categories` */

/*Table structure for table `inventory` */

DROP TABLE IF EXISTS `inventory`;

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `stocks` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `inventory` */

insert  into `inventory`(`id`,`product_name`,`stocks`,`price`,`image_url`,`date_added`,`last_updated`,`category_id`) values 
(4,'mags',20,5600.00,'uploads/683eb85a32245.jpg','2025-06-02 23:08:45','2025-06-03 17:35:34',NULL),
(6,'M3 Headlight',10,980.00,'uploads/683ec472628ce.jpg','2025-06-02 23:41:38','2025-06-03 17:46:26',NULL);

/*Table structure for table `mechanics` */

DROP TABLE IF EXISTS `mechanics`;

CREATE TABLE `mechanics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `mech_username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `mech_username` (`mech_username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `mechanics` */

insert  into `mechanics`(`id`,`firstname`,`lastname`,`mech_username`,`email`,`phone`,`specialization`,`password`,`registration_date`) values 
(1,'Troy','Pangit','troy','troy@gmail.com','453556767','All around','$2y$10$kgeVPoo28W6u7SB7hLixkeUyZJG0XfhZKxlFmETa6MbyIOuVyUxRy','2025-06-02 23:16:27'),
(4,'Lance','carillo','lance','lance@gmai.com','3454565765','','$2y$10$0fF.tLTmFN2fEL9/efZew.B2qGVSq15vb7LmLXjs1hYyKv.UtE6sS','2025-06-03 11:40:04');

/*Table structure for table `sales_log` */

DROP TABLE IF EXISTS `sales_log`;

CREATE TABLE `sales_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `sale_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `sales_log` */

insert  into `sales_log`(`id`,`product_id`,`quantity`,`total_amount`,`sale_date`) values 
(1,4,2,NULL,'2025-06-03 16:41:55'),
(2,6,1,980.00,'2025-06-03 16:51:23'),
(3,4,5,28000.00,'2025-06-03 16:54:01'),
(4,4,3,16800.00,'2025-06-03 17:35:34');

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cus_username` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`firstname`,`lastname`,`email`,`password`,`cus_username`) values 
(7,'Rica Mae','Labangbang','fgdgfg@gmail.com','$2y$10$dEwB5j37VqaK/jNiL0583uauZDJT7Vfd1H3hkZoSONxruFvu0R7f6','Rica'),
(9,'Bonel','Gardon','bonel@gmail.com','$2y$10$V.lcjj6tH903xMK7oEWdDeIg2dypRXk/lZuEnE23.QEfDtH5zF/22','Bonel');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
