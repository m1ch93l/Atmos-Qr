-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 24, 2024 at 03:31 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_atmos`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `academic_year`, `status`, `created_at`, `updated_at`) VALUES
(50, '2024-2025', 'active', '2024-02-24 02:25:12', '2024-02-24 02:25:12');

-- --------------------------------------------------------

--
-- Table structure for table `collegestudents`
--

CREATE TABLE `collegestudents` (
  `ID` int(11) NOT NULL,
  `IdentificationNumber` varchar(100) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Course` enum('ACT','BSCS','ENTREP','BSAIS') NOT NULL,
  `Level` enum('1','2','3','4') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `ID` int(11) NOT NULL,
  `DepartmentName` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`ID`, `DepartmentName`, `created_at`, `updated_at`) VALUES
(14, 'College Department', '2024-02-22 00:16:36', '2024-02-22 00:19:50'),
(15, 'Senior High Department', '2024-02-22 00:16:47', '2024-02-22 00:20:02'),
(16, 'Technical Department', '2024-02-22 00:20:28', '2024-02-22 00:20:28');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `event_name` varchar(100) NOT NULL,
  `event_venue` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_attendees`
--

CREATE TABLE `event_attendees` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `respondent_id` int(11) NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `ID` int(11) NOT NULL,
  `IdentificationNumber` varchar(100) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `PositionID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `ID` int(11) NOT NULL,
  `PositionName` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`ID`, `PositionName`, `created_at`, `updated_at`) VALUES
(1, 'Teacher', '2024-02-17 02:37:12', '2024-02-17 02:51:06'),
(5, 'Registrar', '2024-02-18 18:24:42', '2024-02-19 01:48:18');

-- --------------------------------------------------------

--
-- Table structure for table `registrars`
--

CREATE TABLE `registrars` (
  `id` int(11) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `userName` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seniorhighstudents`
--

CREATE TABLE `seniorhighstudents` (
  `ID` int(11) NOT NULL,
  `IdentificationNumber` varchar(100) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Strand` enum('ABM','GAS','HUMMS','STEM','HE','IA','ICT') DEFAULT NULL,
  `Grade` enum('grade_11','grade_12') DEFAULT NULL,
  `Section` enum('A','B','C','D') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `password`, `user_type_id`, `created_at`, `updated_at`) VALUES
(1, 'Jetro', 'Bagasala', 'Admin', 'Admin123', 1, '2024-02-14 06:53:14', NULL),
(2, 'Cedrick', 'Embestro', 'Registrar', 'Registrar123', 2, '2024-02-14 06:54:55', NULL),
(3, 'Cardo', 'Dalisay', 'Cardo', 'Dalisay', 1, '2024-02-23 17:10:50', '2024-02-23 17:10:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

CREATE TABLE `user_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', '2024-02-14 06:49:34', NULL),
(2, 'Registrar', '2024-02-14 06:49:34', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `academic_year` (`academic_year`);

--
-- Indexes for table `collegestudents`
--
ALTER TABLE `collegestudents`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `IdentificationNumber` (`IdentificationNumber`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `DepartmentName` (`DepartmentName`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `respondent_id` (`respondent_id`);

--
-- Indexes for table `faculties`
--
ALTER TABLE `faculties`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `IdentificationNumber` (`IdentificationNumber`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `faculties_ibfk_1` (`DepartmentID`),
  ADD KEY `faculties_ibfk_2` (`PositionID`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `PositionName` (`PositionName`);

--
-- Indexes for table `registrars`
--
ALTER TABLE `registrars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seniorhighstudents`
--
ALTER TABLE `seniorhighstudents`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `IdentificationNumber` (`IdentificationNumber`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `user_type_id` (`user_type_id`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `collegestudents`
--
ALTER TABLE `collegestudents`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_attendees`
--
ALTER TABLE `event_attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculties`
--
ALTER TABLE `faculties`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `registrars`
--
ALTER TABLE `registrars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seniorhighstudents`
--
ALTER TABLE `seniorhighstudents`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD CONSTRAINT `event_attendees_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `event_attendees_ibfk_2` FOREIGN KEY (`respondent_id`) REFERENCES `respondents` (`id`);

--
-- Constraints for table `faculties`
--
ALTER TABLE `faculties`
  ADD CONSTRAINT `faculties_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `faculties_ibfk_2` FOREIGN KEY (`PositionID`) REFERENCES `positions` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
