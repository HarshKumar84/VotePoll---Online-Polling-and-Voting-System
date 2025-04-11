-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2025 at 09:15 PM
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
-- Database: `poll_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `ca`
--

CREATE TABLE `ca` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ca`
--

INSERT INTO `ca` (`id`, `first_name`, `last_name`, `email`, `password`) VALUES
(1, 'Aishwary', 'Mishra', 'aishmishra987@gmail.com', '$2y$10$rP/Nxc2NF.Xx8tci4r/Ec.zycKDjdt9X63TCqDn6obhRcV6eZ0l.m');

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `allow_multiple_choices` tinyint(1) DEFAULT 0,
  `anonymous_voting` tinyint(1) DEFAULT 0,
  `require_login` tinyint(1) DEFAULT 0,
  `show_results` tinyint(1) DEFAULT 0,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `polls`
--

INSERT INTO `polls` (`id`, `title`, `description`, `category`, `options`, `allow_multiple_choices`, `anonymous_voting`, `require_login`, `show_results`, `end_date`, `created_at`, `user_id`) VALUES
(2, 'Pizza or Burger for dinner?', 'We\'re looking to gather data for which Food people like the most to eat in dinner. Please vote for your favorite choices!', 'food', '[\"Pizza\",\"Burger\",\"Other\"]', 0, 1, 0, 1, NULL, '2025-04-08 17:09:37', 1),
(3, 'What\'s the best streaming service?', 'We\'re looking to gather data on which streaming source most peoples are intrested. Please vote for your favorite streaming source!', 'entertainment', '[\" Netflix\",\"Amazon Prime Video\",\" Disney+\",\" Other\"]', 0, 1, 0, 1, NULL, '2025-04-08 17:11:26', 1),
(4, 'Which sport is the most exciting to watch?', 'We\'re looking to gather data on for which sports most of the peoples are intrested. Please vote for your favorite sports!', 'sports', '[\"Cricket\",\"Football\",\"Basketball\",\"Baseball \\u26bd\\ud83c\\udfc0\\ud83c\\udfcf\\ud83c\\udfbe\\u26be\",\" Other\"]', 0, 1, 0, 1, '2025-05-31 00:00:00', '2025-04-08 17:13:15', 1),
(5, 'Is online learning as effective as in-person?', 'We\'re looking to gather data on Online education is usefull for the student or not. Please vote for your intrest!', 'education', '[\"Yes\",\"No\",\"Other\"]', 0, 1, 0, 1, NULL, '2025-04-08 17:16:01', 1),
(6, 'Should voting be mandatory?', 'We\'re looking to gather data on Online education is usefull for the student or not. Please vote for your intrest!', 'politics', '[\"Yes\",\"No\",\"Other\"]', 0, 1, 0, 1, NULL, '2025-04-08 17:17:14', 1),
(7, 'Is pineapple on pizza acceptable?', 'We\'re looking to gather data on Online education is usefull for the student or not. Please vote for your intrest!', 'food', '[\"Yes\",\"No\",\"Other\"]', 0, 1, 0, 1, NULL, '2025-04-08 18:39:55', 1),
(8, 'Movies or TV shows?', 'We\'re looking to gather data on which tv shows most peoples are intrested. Please vote for your favorite shows!', 'entertainment', '[\"The Dark Knight\",\"Interstellar\",\" Bhabhi ji Gharpar hai\",\"Tarak Mehta ka Ulta Chashma\",\" Other\"]', 0, 1, 0, 1, NULL, '2025-04-08 19:05:18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `poll_comments`
--

CREATE TABLE `poll_comments` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_index` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`id`, `poll_id`, `option_index`, `user_id`, `created_at`) VALUES
(6, 6, 0, 1, '2025-04-08 18:31:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ca`
--
ALTER TABLE `ca`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `poll_comments`
--
ALTER TABLE `poll_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_comments_ibfk_1` (`poll_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `votes_ibfk_1` (`poll_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ca`
--
ALTER TABLE `ca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `poll_comments`
--
ALTER TABLE `poll_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `poll_comments`
--
ALTER TABLE `poll_comments`
  ADD CONSTRAINT `poll_comments_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
