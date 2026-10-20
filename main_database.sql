-- Combined SQL dump generated from multiple exported SQL files
-- Files included:
-- arthurnerritech_arthurnerri.sql
-- arthurnerritech_library.sql
-- arthurnerri_users.sql
-- arthurnerritech_library2.sql
-- library_arthurnerri.sql

-- Note: This file concatenates the original dumps. Importing may produce errors if tables already exist or if there are duplicate/conflicting statements. If you want deduplication or DROP TABLE handling, tell me and I will adjust.

/* -----------------------------
   Start: arthurnerritech_arthurnerri.sql
   ----------------------------- */

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 16, 2025 at 09:41 PM
-- Server version: 8.0.43-cll-lve
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `arthurnerritech_arthurnerri`
--

-- --------------------------------------------------------

--
-- Table structure for table `arthurnerri_users`
--

CREATE TABLE `arthurnerri_users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('residents','brgyOfficer','mdrrmOfficer','systemAdmin') COLLATE utf8mb4_general_ci NOT NULL,
  `barangay` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `username` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_engagement`
--

CREATE TABLE `community_engagement` (
  `id` int NOT NULL,
  `officer_in_charge` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `population` int NOT NULL,
  `attendees` int NOT NULL,
  `engagement_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `image_file` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `report_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_requests`
--

CREATE TABLE `email_requests` (
  `id` int NOT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `sender_email` varchar(255) DEFAULT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_html` tinyint(1) DEFAULT '0',
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `brand_model` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `item_condition` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('unread','read') COLLATE utf8mb4_general_ci DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `contact` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `residence` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `incident_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `report_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Declined','Processing','Accepted') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `image_file` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `upload1`
--

CREATE TABLE `upload1` (
  `id` int NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `arthurnerri_users`
--
ALTER TABLE `arthurnerri_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `community_engagement`
--
ALTER TABLE `community_engagement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_requests`
--
ALTER TABLE `email_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `upload1`
--
ALTER TABLE `upload1`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `arthurnerri_users`
--
ALTER TABLE `arthurnerri_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_engagement`
--
ALTER TABLE `community_engagement`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_requests`
--
ALTER TABLE `email_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `upload1`
--
ALTER TABLE `upload1`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `arthurnerri_users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

/* -----------------------------
   End: arthurnerritech_arthurnerri.sql
   ----------------------------- */


/* -----------------------------
   Start: arthurnerritech_library.sql
   ----------------------------- */

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 08:03 AM
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
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `username` varchar(191) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brgy_audit_log`
--

CREATE TABLE `brgy_audit_log` (
  `id` int(11) NOT NULL,
  `username` varchar(191) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `theme` varchar(255) NOT NULL,
  `date_added` date NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `cover_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `theme` varchar(255) NOT NULL,
  `date_added` date NOT NULL,
  `publisher` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `video_data` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brgy_audit_log`
--
ALTER TABLE `brgy_audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brgy_audit_log`
--
ALTER TABLE `brgy_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

/* -----------------------------
   End: arthurnerritech_library.sql
   ----------------------------- */


/* -----------------------------
   Start: arthurnerri_users.sql
   ----------------------------- */

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2025 at 05:00 AM
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
-- Database: `arthurnerritech_arthurnerri`
--

-- --------------------------------------------------------

--
-- Table structure for table `arthurnerri_users`
--

CREATE TABLE `arthurnerri_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('residents','brgyOfficer','mdrrmOfficer','systemAdmin') NOT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `arthurnerri_users`
--
ALTER TABLE `arthurnerri_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `arthurnerri_users`
--
ALTER TABLE `arthurnerri_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

/* -----------------------------
   End: arthurnerri_users.sql
   ----------------------------- */


/* -----------------------------
   Start: arthurnerritech_library2.sql
   ----------------------------- */

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

/* -----------------------------
   End: arthurnerritech_library2.sql
   ----------------------------- */


/* -----------------------------
   Start: library_arthurnerri.sql
   ----------------------------- */

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2025 at 07:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `theme` varchar(255) NOT NULL,
  `date_added` date NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `cover_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `theme` varchar(255) NOT NULL,
  `date_added` date NOT NULL,
  `publisher` varchar(255) NOT NULL,
  `views` int(11) DEFAULT 0,
  `file_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `infograph`
--
ALTER TABLE `infograph`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `infograph`
--
ALTER TABLE `infograph`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

/* -----------------------------
   End: library_arthurnerri.sql
   ----------------------------- */
