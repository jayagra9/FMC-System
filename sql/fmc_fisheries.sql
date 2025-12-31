-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Dec 31, 2025 at 05:24 AM
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
-- Database: `fmc_fisheries`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_audit`
--

CREATE TABLE `activity_audit` (
  `id` int(11) NOT NULL,
  `table_name` varchar(128) NOT NULL,
  `record_id` bigint(20) NOT NULL,
  `action` enum('create','update','delete') NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` datetime NOT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for table `border_crossings`
--

CREATE TABLE `border_crossings` (
  `id` int(11) NOT NULL,
  `vessel_imo_number` varchar(50) NOT NULL,
  `eez` varchar(100) DEFAULT NULL,
  `owner_informed_datetime` datetime DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `first_notice` varchar(100) DEFAULT NULL,
  `after_72hr_boat_status` varchar(255) DEFAULT NULL,
  `date_of_investigation` datetime DEFAULT NULL,
  `called_owner_to_inform_dc` varchar(255) DEFAULT NULL,
  `test_message_correct` varchar(255) DEFAULT NULL,
  `departure_date` datetime DEFAULT NULL,
  `after_72hr_remark` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `border_crossings_audit`
--

CREATE TABLE `border_crossings_audit` (
  `id` int(11) NOT NULL,
  `border_crossing_id` int(11) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` datetime NOT NULL,
  `action` varchar(32) NOT NULL,
  `changes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `border_crossing_alerts`
--

CREATE TABLE `border_crossing_alerts` (
  `id` int(11) NOT NULL,
  `vessel_id` int(11) NOT NULL,
  `vessel_name` varchar(100) NOT NULL,
  `imo_number` varchar(50) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `crossing_point` varchar(100) DEFAULT NULL,
  `crossing_date` datetime DEFAULT NULL,
  `departure_country` varchar(50) DEFAULT NULL,
  `destination_country` varchar(50) DEFAULT NULL,
  `status` enum('pending','notified','cleared','denied') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `distress_vessels`
--

CREATE TABLE `distress_vessels` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `vessel_name` varchar(100) NOT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `speed` varchar(50) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `date_time_detection` datetime DEFAULT NULL,
  `distance_last_position` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `departure_form` varchar(255) DEFAULT NULL,
  `voyage` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `record_number` varchar(50) NOT NULL,
  `iom_zms` varchar(10) NOT NULL,
  `imul_no` varchar(100) NOT NULL,
  `bt_sn` varchar(100) DEFAULT NULL,
  `installed_date` date DEFAULT NULL,
  `home_port` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `current_status` varchar(50) DEFAULT NULL,
  `feedback_to_call` text DEFAULT NULL,
  `service_done_date` date DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `installation_checklist` text DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `silent_vessels`
--

CREATE TABLE `silent_vessels` (
  `id` int(11) NOT NULL,
  `vessel_name` varchar(100) NOT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `owner_contact_number` varchar(20) DEFAULT NULL,
  `relevant_harbour` varchar(100) DEFAULT NULL,
  `owner_information_date` date DEFAULT NULL,
  `owner_informed` varchar(50) DEFAULT NULL,
  `sms_to_owner` varchar(50) DEFAULT NULL,
  `date_to_investigate` date DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `silent_vessel_alerts`
--

CREATE TABLE `silent_vessel_alerts` (
  `id` int(11) NOT NULL,
  `vessel_id` int(11) NOT NULL,
  `vessel_name` varchar(100) NOT NULL,
  `imo_number` varchar(50) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `owner_contact` varchar(20) DEFAULT NULL,
  `relevant_harbour` varchar(100) DEFAULT NULL,
  `last_known_position` varchar(100) DEFAULT NULL,
  `last_signal_time` datetime DEFAULT NULL,
  `owner_informed` enum('yes','no') DEFAULT 'no',
  `sms_to_owner` varchar(20) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('silent','active','resolved') DEFAULT 'silent',
  `reported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `vessel_owners`
--

CREATE TABLE `vessel_owners` (
  `id` int(11) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes for table `activity_audit`
--
ALTER TABLE `activity_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_name` (`table_name`),
  ADD KEY `record_id` (`record_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user` (`user_id`),
  ADD KEY `idx_activity_timestamp` (`timestamp`);

--
-- Indexes for table `border_crossings`
--
ALTER TABLE `border_crossings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `border_crossings_audit`
--
ALTER TABLE `border_crossings_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_border_crossing` (`border_crossing_id`),
  ADD KEY `idx_changed_by` (`changed_by`);

--
-- Indexes for table `border_crossing_alerts`
--
ALTER TABLE `border_crossing_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_alert_vessel` (`vessel_id`),
  ADD KEY `idx_alert_status` (`status`);

--
-- Indexes for table `distress_vessels`
--
ALTER TABLE `distress_vessels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_number` (`record_number`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `silent_vessels`
--
ALTER TABLE `silent_vessels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `silent_vessel_alerts`
--
ALTER TABLE `silent_vessel_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_silent_vessel` (`vessel_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);


-- AUTO_INCREMENT for table `activity_audit`
--
ALTER TABLE `activity_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `border_crossings`
--
ALTER TABLE `border_crossings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `border_crossings_audit`
--
ALTER TABLE `border_crossings_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `border_crossing_alerts`
--
ALTER TABLE `border_crossing_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `distress_vessels`
--
ALTER TABLE `distress_vessels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `silent_vessels`
--
ALTER TABLE `silent_vessels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `silent_vessel_alerts`
--
ALTER TABLE `silent_vessel_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `border_crossing_alerts`
--
ALTER TABLE `border_crossing_alerts`
  ADD CONSTRAINT `border_crossing_alerts_ibfk_1` FOREIGN KEY (`vessel_id`) REFERENCES `vessels` (`id`),
  ADD CONSTRAINT `border_crossing_alerts_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `silent_vessel_alerts`
--
ALTER TABLE `silent_vessel_alerts`
  ADD CONSTRAINT `silent_vessel_alerts_ibfk_1` FOREIGN KEY (`vessel_id`) REFERENCES `vessels` (`id`),
  ADD CONSTRAINT `silent_vessel_alerts_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`);

ALTER TABLE services
  ADD COLUMN updated_by INT NULL AFTER created_by,
  ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP;

