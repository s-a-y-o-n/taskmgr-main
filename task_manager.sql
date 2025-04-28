-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 28, 2025 at 01:22 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `task_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `status`, `priority`, `created_at`, `updated_at`) VALUES
(1, 1, 'dfknhsjdkfks', 'dsjfskdbskdfks', '2025-04-18 00:00:00', 'pending', 'medium', '2025-04-09 00:37:04', '2025-04-09 00:37:04'),
(2, 1, 'new ', 'new one', '2025-04-10 03:26:00', 'in_progress', 'high', '2025-04-09 00:49:17', '2025-04-09 00:49:17'),
(3, 3, 'gyuuu', 'hhhhh', '2025-04-12 03:03:00', 'pending', 'medium', '2025-04-11 10:06:40', '2025-04-11 10:06:40'),
(6, 4, 'shdhjasjdj', 'ajshdajsvdj', '2025-05-03 00:00:00', 'completed', 'medium', '2025-04-27 16:09:41', '2025-04-27 16:13:53'),
(7, 4, 'today', 'sjndjajsdbka ai sudda diaudad ads aud', '2025-04-27 18:00:00', 'completed', 'medium', '2025-04-27 16:15:04', '2025-04-27 16:15:55'),
(9, 4, 'cjhdbcsjd', 'snhavsdjas adsg', '2025-04-08 00:00:00', 'pending', 'medium', '2025-04-27 16:30:28', '2025-04-27 16:30:28'),
(10, 6, 'new task', 'this is the discription', '2025-05-10 00:04:00', 'completed', 'medium', '2025-04-27 16:44:40', '2025-04-27 16:49:35'),
(11, 6, 'ghsdhas', 'ashdvajsdjash asdhjas', '2025-05-02 00:00:00', 'pending', 'high', '2025-04-27 16:50:23', '2025-04-27 16:50:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remember_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`, `remember_token`) VALUES
(1, 'ajay', 'ajay@gmail.com', '$2y$10$fnID6VFXiaVhg9g4W5lUKuvUG7sioBTjK4D.2nUz8Q1mm6gkHH9E.', '2025-04-09 00:16:43', '2025-04-09 00:16:43', NULL),
(2, 'alen', 'alen@gmail.com', '$2y$10$auIgRo2uWLp6hXpCZfxz9unp7DhsO/FrZcPbxVUMi.zRbKrMntAnO', '2025-04-11 10:03:24', '2025-04-11 10:03:24', NULL),
(3, 'sayon', 'sayon@gmail.com', '$2y$10$rGoNt8Ibn0AH9nEGPjl01OaJsvqkmHKdFhbQqP0ZQW6wYCMwwbvXi', '2025-04-11 10:05:44', '2025-04-11 10:05:44', NULL),
(4, 'aaaa', 'aaaa@gmail.com', '$2y$10$L0xS0yz9uoDuzRxvulb/B.V/dCChcugGTlu.9eaU03yk7q.SlfVjK', '2025-04-27 15:51:10', '2025-04-27 15:51:10', NULL),
(5, 'BB', 'bbbb@gmail.com', '$2y$10$9t/roCVUSM1UWPu5330J8OXl6OZNgF0p8SWvZ7gO3hJ6WbfYWlQJy', '2025-04-27 16:41:01', '2025-04-27 16:41:01', NULL),
(6, 'CC', 'cccc@gmail.com', '$2y$10$TiN1fkHGCAjvmrhw/47z4.DYqL4QYZSmxmqC2HZNoGiarybGAVOdC', '2025-04-27 16:43:41', '2025-04-27 16:43:41', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
