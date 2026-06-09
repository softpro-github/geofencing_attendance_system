-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2026 at 03:04 PM
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
-- Database: `crevance_attend`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `matric_number` varchar(20) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `accuracy` double DEFAULT NULL,
  `device` varchar(250) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `distance` double DEFAULT NULL,
  `face_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `matric_number`, `course_code`, `latitude`, `longitude`, `accuracy`, `device`, `timestamp`, `session_id`, `ip_address`, `distance`, `face_photo`) VALUES
(1, 'ICT/225230090', 'COM112', 6.3332, 5.6238, 105508, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-27 09:43:47', 4, '105.112.106.179', 0, NULL),
(2, 'ICT/2252300007', 'COM123', 6.3332, 5.6238, 22402, 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36 Edg/136.0.0.0', '2025-05-27 17:38:58', 13, '105.112.215.71', 0, NULL),
(4, 'ict/2252060317', 'CSC313', 6.5243793, 3.3792057, 532081.7902787765, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-03 12:30:28', 18, '105.112.215.169', 0, NULL),
(7, 'CSC/CVS/24007951', 'CSC 423', 5.49, 7.05, 50000, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 05:15:21', 24, '::1', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `lecturer_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `expected_lat` double DEFAULT NULL,
  `expected_lng` double DEFAULT NULL,
  `accuracy` double DEFAULT NULL,
  `radius` int(11) DEFAULT 50,
  `department` varchar(250) DEFAULT NULL,
  `level` varchar(250) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`id`, `course_code`, `lecturer_id`, `status`, `started_at`, `expected_lat`, `expected_lng`, `accuracy`, `radius`, `department`, `level`, `expires_at`) VALUES
(3, 'COM290', 5, 'inactive', '2025-05-27 09:30:58', 6.3332, 5.6238, 105508, 50, 'Computer Sci.', '400 Level', '2026-05-26 04:39:15'),
(4, 'COM112', 5, 'inactive', '2025-05-27 09:36:21', 6.3332, 5.6238, 105508, 50, 'Mass Comm.', '400 Level', '2026-05-25 04:31:23'),
(12, 'COM290', 5, 'inactive', '2025-05-27 10:23:13', 7.050952, 6.2769317, 1600, 50, 'Computer Sci.', '400 Level', '2026-05-25 04:31:23'),
(13, 'COM123', 7, 'inactive', '2025-05-27 17:33:19', 6.3332, 5.6238, 105508, 50, 'Computer Sci.', '400 Level', '2026-05-25 04:31:23'),
(14, 'COM518', 5, 'inactive', '2025-05-28 06:44:08', 7.063063063063063, 6.263749215943017, 2000, 50, 'Computer Sci.', '400 Level', '2026-05-25 04:31:23'),
(15, 'COM518', 5, 'inactive', '2025-05-28 07:12:19', 7.063063063063063, 6.263749215943017, 2000, 50, 'Computer Sci.', '300 Level', '2026-05-25 04:31:23'),
(16, 'COM518', 5, 'inactive', '2025-05-28 09:47:07', 7.063063063063063, 6.263749215943017, 2000, 50, 'Computer Sci.', '400 Level', '2026-05-25 04:31:23'),
(18, 'CSC313', 8, 'inactive', '2025-06-03 12:30:14', 6.5243793, 3.3792057, 532081.7902787765, 50, 'Computer Sci.', '300 Level', '2026-05-25 04:31:23'),
(20, 'COM564', 1, 'inactive', '2025-06-11 10:28:14', 6.21, 5.64, 50000, 50, 'Computer Sci.', '400 Level', '2026-05-25 04:31:23'),
(23, 'CSC 423', 8, 'inactive', '2026-05-26 04:55:39', 5.49, 7.05, 50000, 50, 'Computer Sci.', '400 Level', '2026-05-26 14:10:39'),
(24, 'CSC 423', 8, 'inactive', '2026-05-26 05:12:40', 5.49, 7.05, 50000, 50, 'Computer Sci.', '400 Level', '2026-05-26 14:27:40');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `department` varchar(250) DEFAULT NULL,
  `level` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `lecturer_id`, `course_code`, `department`, `level`) VALUES
(1, 5, 'COM290', 'Computer Sci.', '200 Level'),
(2, 5, 'COM112', 'Mass Comm.', '400 Level'),
(3, 1, 'COM137', 'Statistics', '200 Level'),
(4, 7, 'COM123', 'Computer Sci.', '400 Level'),
(5, 5, 'COM518', 'Computer Sci.', '400 Level'),
(7, 8, 'CSC313', 'Computer Sci.', '300 Level'),
(8, 1, 'COM564', 'Computer Sci.', '400 Level'),
(9, 1, 'COM566', 'Computer Sci.', '400 Level'),
(10, 8, 'CSC 423', 'Computer Sci.', '400 Level'),
(11, 8, 'CSC 426', 'Computer Sci.', '400 Level');

-- --------------------------------------------------------

--
-- Table structure for table `lecturers`
--

CREATE TABLE `lecturers` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lecturers`
--

INSERT INTO `lecturers` (`id`, `username`, `password`) VALUES
(1, 'admin1', '$2y$10$DZZorFIqF3LudfxJDOt9.eY7T7bgWuKb050igSk8fiDYqfg.neAgK'),
(5, 'admin2', '$2y$10$pVTHtx5CsdheuYttktHKZ.HevArc1...yFpAqZvyxslPJd/onBps2'),
(7, 'ICT/225230090', '$2y$10$arwoq4hTJ.KI7tGtT4gfL.bb5X8Nhe7kKclBZrS5BKjPhUA6rL88W'),
(8, 'Softpro', '$2y$10$gnRD9KOW56UhNb1lJbhfCuqCQkGPgd4r16rIRwbUKymfBFGioA5l.');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `matric_number` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `matric_number`, `department`, `level`, `password`) VALUES
(2, 'Chris ', 'ICT/2252300470', 'Computer Sci.', '300 Level', '$2y$10$gnRD9KOW56UhNb1lJbhfCuqCQkGPgd4r16rIRwbUKymfBFGioA5l.'),
(5, 'Christian Osaro', 'ICT/2252300007', 'Computer Sci.', '400 Level', '$2y$10$rMX7yaiJASBfnQHPNLwHAu7h8BSYoFbNxHpVT/k9RzjtWEsutJ8ou'),
(6, 'Cot Code', 'ICT/225230090', 'Mass Comm.', '400 Level', '$2y$10$Z2wogQDAeIlmSHn6M4T4Au5YsMFZ5aS4.YLnmD6hclMrAvYxGY92.'),
(7, 'Kadiri Asemhokai', 'ICT/2252060316', 'Computer Sci.', '300 Level', '$2y$10$BtTlr.FdFN4uFV6JvqlBlOniLsOQWjCKQHSLrxoz88ZzmBCsht7tu'),
(8, 'Otse Henry', 'ict/2252060317', 'Computer Sci.', '300 Level', '$2y$10$eW.O3qJdbS.lgBiUEmZ4FeX2ejX.2gfK4YsL2ODZuPvrZ6q309HF.'),
(10, 'Godsent Kadiri', 'ict/2252060319', 'Computer Sci.', '300 Level', '$2y$10$.CDtqiULIXmz1ZrwmKb/9.N8iOtqJT2OKTmuxdqviVJKaA6mWhWOy'),
(14, 'Emmanuel Kadiri', 'ict/2252060212', 'Computer Sci.', '300 Level', '$2y$10$tBQ6u.3aoH6LLGyC57pGn.wP2WehoPJjVCtsTKsZXUrXEcTIUmmqm'),
(15, 'Kadiri Precious Eghosa', 'CSC/CVS/24007951', 'Computer Sci.', '400 Level', '$2y$10$V0MmYv851Ks9cfgse3Bzy.mr.QnkXiC9UKIy9X7vDTgSNOfCN2xS.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `lecturers`
--
ALTER TABLE `lecturers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matric_number` (`matric_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `lecturers`
--
ALTER TABLE `lecturers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
