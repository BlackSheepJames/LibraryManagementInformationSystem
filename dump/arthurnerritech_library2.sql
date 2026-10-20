-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 03:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `arthurnerritech_library`
--

-- --------------------------------------------------------

--
-- Table structure for table `infograph`
--

CREATE TABLE `infograph` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `theme` varchar(255) NOT NULL,
  `date_added` date NOT NULL,
  `image` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `theme` varchar(255) NOT NULL,
  `date_added` date NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `cover_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `theme` varchar(255) NOT NULL,
  `date_added` date NOT NULL,
  `publisher` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `video_data` LONGBLOB DEFAULT NULL
  ,PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(191) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `brgy_audit_log`
--

CREATE TABLE `brgy_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(191) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
