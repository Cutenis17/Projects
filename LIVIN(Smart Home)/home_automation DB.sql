-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 11:56 AM
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
-- Database: `home_automation`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `alert_type` varchar(20) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `alert_type`, `active`, `created_at`, `resolved_at`) VALUES
(1, 'smoke', 0, '2025-04-24 05:16:16', '2025-04-27 05:32:51'),
(2, 'smoke', 0, '2025-04-27 05:34:12', '2025-04-27 05:34:39'),
(3, 'smoke', 0, '2025-04-27 05:37:32', '2025-04-27 05:56:29'),
(4, 'smoke', 0, '2025-04-27 05:57:37', '2025-04-27 05:58:13'),
(5, 'smoke', 0, '2025-04-27 06:07:52', '2025-04-27 06:08:08'),
(6, 'smoke', 0, '2025-04-28 13:48:22', '2025-04-28 13:48:38'),
(7, 'smoke', 0, '2025-04-29 08:30:34', '2025-04-29 08:31:45'),
(8, 'smoke', 0, '2025-05-01 03:05:12', '2025-05-01 03:05:17'),
(9, 'smoke', 0, '2025-05-01 04:17:27', '2025-05-01 04:17:48'),
(10, 'smoke', 0, '2025-05-02 07:21:49', '2025-05-02 08:02:49'),
(11, 'smoke', 0, '2025-05-02 10:01:59', '2025-05-02 10:02:53'),
(12, 'smoke', 0, '2025-05-02 10:02:54', '2025-05-02 10:02:55'),
(13, 'smoke', 0, '2025-05-02 10:03:09', '2025-05-02 10:03:43'),
(14, 'smoke', 0, '2025-05-03 01:18:23', '2025-05-03 01:20:30'),
(15, 'smoke', 0, '2025-05-03 01:53:37', '2025-05-03 01:53:46'),
(16, 'smoke', 0, '2025-05-03 01:54:37', '2025-05-03 01:54:48'),
(17, 'smoke', 0, '2025-05-03 01:58:28', '2025-05-03 01:58:39'),
(18, 'smoke', 0, '2025-05-03 01:58:58', '2025-05-03 01:59:07'),
(19, 'smoke', 0, '2025-05-03 01:59:23', '2025-05-03 01:59:27'),
(20, 'smoke', 0, '2025-05-03 02:06:24', '2025-05-03 02:06:48'),
(21, 'smoke', 0, '2025-05-03 07:12:36', '2025-05-03 07:12:48'),
(22, 'smoke', 0, '2025-05-03 07:12:51', '2025-05-03 07:12:59'),
(23, 'smoke', 0, '2025-05-06 05:34:47', '2025-05-06 05:35:21'),
(24, 'smoke', 0, '2025-05-06 06:37:21', '2025-05-06 06:37:27'),
(25, 'smoke', 0, '2025-05-06 09:17:02', '2025-05-06 09:21:32'),
(26, 'smoke', 0, '2025-05-06 09:21:32', '2025-05-06 09:24:10'),
(27, 'smoke', 0, '2025-05-07 13:11:55', '2025-05-07 13:12:03'),
(28, 'smoke', 0, '2025-05-07 13:25:09', '2025-05-07 13:25:31'),
(29, 'smoke', 0, '2025-05-07 15:27:49', '2025-05-07 15:28:02'),
(30, 'smoke', 0, '2025-05-07 15:28:45', '2025-05-07 15:35:25'),
(31, 'smoke', 0, '2025-05-07 15:37:02', '2025-05-07 15:38:56'),
(32, 'smoke', 0, '2025-05-07 15:39:42', '2025-05-07 15:39:52'),
(33, 'smoke', 0, '2025-05-07 15:42:46', '2025-05-07 16:03:47'),
(34, 'smoke', 0, '2025-05-15 09:45:55', '2025-05-15 09:46:25'),
(35, 'smoke', 0, '2025-05-15 09:46:53', '2025-05-15 09:46:58');

-- --------------------------------------------------------

--
-- Table structure for table `relays`
--

CREATE TABLE `relays` (
  `id` int(11) NOT NULL,
  `relay_number` tinyint(4) NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `manual_control` tinyint(1) DEFAULT 0,
  `last_trigger_time` datetime DEFAULT NULL,
  `activation_source` varchar(20) NOT NULL DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `relays`
--

INSERT INTO `relays` (`id`, `relay_number`, `state`, `last_updated`, `manual_control`, `last_trigger_time`, `activation_source`) VALUES
(1, 1, 0, '2025-05-15 09:51:41', 1, '2025-05-10 22:40:13', 'manual'),
(2, 2, 0, '2025-05-15 09:51:13', 0, NULL, 'manual');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `relay_number` tinyint(4) NOT NULL,
  `on_time` time NOT NULL,
  `off_time` time NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `relay_number`, `on_time`, `off_time`, `active`, `created_at`) VALUES
(1, 1, '11:11:00', '11:12:00', 0, '2025-05-01 03:10:36'),
(2, 2, '11:11:00', '11:12:00', 0, '2025-05-01 03:10:36'),
(3, 1, '12:24:00', '12:25:00', 0, '2025-05-01 04:23:33'),
(4, 2, '12:24:00', '12:25:00', 0, '2025-05-01 04:23:33'),
(5, 1, '16:04:00', '16:05:00', 0, '2025-05-01 08:03:30'),
(6, 2, '16:04:00', '16:05:00', 0, '2025-05-01 08:03:30'),
(7, 1, '11:53:00', '11:54:00', 0, '2025-05-02 03:52:39'),
(8, 2, '11:53:00', '11:54:00', 0, '2025-05-02 03:52:39'),
(9, 1, '21:02:00', '21:03:00', 0, '2025-05-02 13:01:34'),
(10, 2, '21:02:00', '21:03:00', 0, '2025-05-02 13:01:34'),
(11, 1, '10:05:00', '10:06:00', 0, '2025-05-03 02:04:20'),
(12, 2, '10:05:00', '10:06:00', 0, '2025-05-03 02:04:20'),
(13, 1, '18:49:00', '18:50:00', 0, '2025-05-05 10:48:31'),
(14, 2, '18:49:00', '18:50:00', 0, '2025-05-05 10:48:31'),
(15, 1, '14:25:00', '14:26:00', 0, '2025-05-06 05:24:35'),
(16, 2, '14:25:00', '14:26:00', 0, '2025-05-06 05:24:35'),
(17, 1, '13:26:00', '13:27:00', 0, '2025-05-06 05:25:54'),
(18, 2, '13:26:00', '13:27:00', 0, '2025-05-06 05:25:54'),
(19, 1, '17:09:00', '17:10:00', 0, '2025-05-06 09:08:56'),
(20, 2, '17:09:00', '17:10:00', 0, '2025-05-06 09:08:56'),
(21, 1, '21:24:00', '21:25:00', 0, '2025-05-07 13:23:55'),
(22, 2, '21:24:00', '21:25:00', 0, '2025-05-07 13:23:55'),
(23, 1, '00:02:00', '00:03:00', 0, '2025-05-07 16:01:44'),
(24, 2, '00:02:00', '00:03:00', 0, '2025-05-07 16:01:44'),
(25, 1, '23:38:00', '23:39:00', 0, '2025-05-13 15:37:34'),
(26, 2, '23:38:00', '23:39:00', 0, '2025-05-13 15:37:34'),
(27, 1, '12:12:00', '12:13:00', 1, '2025-05-15 04:11:07'),
(28, 2, '12:12:00', '12:13:00', 1, '2025-05-15 04:11:07');

-- --------------------------------------------------------

--
-- Table structure for table `smoke_logs`
--

CREATE TABLE `smoke_logs` (
  `id` int(11) NOT NULL,
  `sensor_value` int(11) NOT NULL,
  `alert_triggered` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `smoke_logs`
--

INSERT INTO `smoke_logs` (`id`, `sensor_value`, `alert_triggered`, `updated_at`) VALUES
(1, 53, 0, '2025-05-15 09:47:09');

-- --------------------------------------------------------

--
-- Table structure for table `ultrasonic_logs`
--

CREATE TABLE `ultrasonic_logs` (
  `id` int(11) NOT NULL,
  `distance` int(11) NOT NULL,
  `triggered` tinyint(1) NOT NULL DEFAULT 0,
  `last_motion_time` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ultrasonic_logs`
--

INSERT INTO `ultrasonic_logs` (`id`, `distance`, `triggered`, `last_motion_time`, `updated_at`) VALUES
(1, 64, 0, '2025-05-15 09:51:23', '2025-05-15 09:51:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '12345678', 'admin', '2025-05-02 08:35:12', '2025-05-07 13:16:42'),
(3, 'christian', '123451234', 'user', '2025-05-08 01:49:22', '2025-05-08 01:49:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `relays`
--
ALTER TABLE `relays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smoke_logs`
--
ALTER TABLE `smoke_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ultrasonic_logs`
--
ALTER TABLE `ultrasonic_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `relays`
--
ALTER TABLE `relays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `smoke_logs`
--
ALTER TABLE `smoke_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ultrasonic_logs`
--
ALTER TABLE `ultrasonic_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
