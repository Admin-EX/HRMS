-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 10:08 AM
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
-- Database: `hrms`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `employee_number` varchar(60) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `read_status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `employee_number`, `title`, `content`, `url`, `read_status`, `date`) VALUES
(1, 'BPC-2024-001', 'approved Leave Document', 'your application for leave has been approved ', '/activity.php', 'unread', '2026-05-21 12:00:19'),
(2, 'EMP001', 'approved Leave Document', 'you have been approved application of Employee BPC-2024-001 ', '/leave.php', 'unread', '2026-05-21 12:00:19'),
(3, 'BPC-2024-001', 'approved Leave Document', 'your application for leave has been approved ', '/activity.php', 'unread', '2026-05-21 13:37:59'),
(4, 'EMP001', 'approved Leave Document', 'you have been approved application of Employee BPC-2024-001 ', '/leave.php', 'unread', '2026-05-21 13:37:59'),
(6, 'BPC-2019-001', 'approved Leave Document', 'your application for leave has been approved ', '/activity.php', 'unread', '2026-05-21 16:07:17'),
(7, 'EMP001', 'approved Leave Document', 'you have been approved application of Employee BPC-2019-001 ', '/leave.php', 'unread', '2026-05-21 16:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `announcement_date` date NOT NULL,
  `priority` varchar(20) DEFAULT 'Normal',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_number` varchar(30) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `employee_type` enum('TP','NTP') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `credentials` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` varchar(60) NOT NULL,
  `educational_attainment` varchar(60) NOT NULL,
  `school` varchar(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_hired` varchar(60) DEFAULT NULL,
  `type` varchar(60) NOT NULL,
  `years_service` int(11) NOT NULL,
  `employment_status` varchar(11) NOT NULL,
  `leave_balance` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_number`, `full_name`, `employee_type`, `department`, `position`, `credentials`, `gender`, `address`, `phone`, `email`, `status`, `educational_attainment`, `school`, `created_at`, `date_hired`, `type`, `years_service`, `employment_status`, `leave_balance`) VALUES
(1, 'BPC-2019-001', 'Maria Santos', 'TP', 'Engineering', 'Professor', 'Doctorate', 'Male', '123 Main St, Manila', '09171234567', 'maria.santos@btech.edu', 'Contractual', 'Masters', 'BSU', '2026-02-03 15:53:16', '2010-02-05', '', 0, 'Active', ''),
(2, 'BPC-2020-045', 'Juan Dela Cruz', 'TP', 'Education', 'Instructor', 'Masters', 'Male', '456 Oak Ave, Quezon City', '09229876543', 'juan.delacruz@btech.edu', 'Permanent', 'taking Masters', 'PUP', '2026-02-03 15:53:16', '2011-02-05', '', 0, 'Resigned', ''),
(3, 'BPC-2024-001', 'Dr. Maria Santos', 'TP', 'College of Engineering', 'Faculty', 'PhD in Computer Science', 'Female', '123 Main Street, Manila', '09171234567', 'maria.santos@btech.edu.ph', 'COS', 'Doctorate', 'UP', '2026-02-03 17:41:54', '2024-01-15', '', 0, 'Active', ''),
(11, 'BPC-2019-00525452', 'Ariel gallardo Labuson', 'TP', 'IT', 'Instructor', 'Bachelor', 'Male', '625 Tramo Santo Cristo', '+639451570794', 'ariellabuson08@gmail.com', 'Permanent', 'Bachelor', 'BSU', '2026-02-04 19:02:17', '2025-02-02', 'TP', 1, 'Active', '15');

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` int(11) NOT NULL,
  `employee_number` varchar(60) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected','for_revision','missing') NOT NULL DEFAULT 'pending',
  `upload_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_number` varchar(60) NOT NULL,
  `type` varchar(60) NOT NULL,
  `start_date` varchar(50) NOT NULL,
  `end_date` varchar(60) NOT NULL,
  `days` varchar(60) NOT NULL,
  `emergency_contact` varchar(60) NOT NULL,
  `reason` int(11) NOT NULL,
  `status` varchar(60) NOT NULL,
  `denial_reason` text DEFAULT NULL,
  `override_reason` text DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_number`, `type`, `start_date`, `end_date`, `days`, `emergency_contact`, `reason`, `status`, `denial_reason`, `override_reason`, `approved_date`, `date_created`) VALUES
(1, 'BPC-2019-001', 'Vacation Leave', '2026-05-05', '2026-05-10', '4', '09451570794', 0, 'approved', NULL, NULL, '2026-05-21', '2026-02-03 16:37:06'),
(2, 'BPC-2020-045', 'Sick Leave', '2026-03-12', '2026-03-13', '2', '09171234567', 0, 'denied', 'sample', NULL, NULL, '2026-02-03 16:37:06'),
(3, 'BPC-2024-001', 'Bereavement Leave', '2026-04-01', '2026-04-03', '3', '09981234567', 0, 'pending', NULL, NULL, NULL, '2026-02-03 16:37:06');

-- --------------------------------------------------------

--
-- Table structure for table `offset`
--

CREATE TABLE `offset` (
  `id` int(50) NOT NULL,
  `employee_number` varchar(60) NOT NULL,
  `subject_code` varchar(60) NOT NULL,
  `subject_description` varchar(60) NOT NULL,
  `academic_term` varchar(60) NOT NULL,
  `schedule_section` varchar(60) NOT NULL,
  `original_sched_date` varchar(60) NOT NULL,
  `original_sched_time` varchar(60) NOT NULL,
  `offset_sched_date` varchar(60) NOT NULL,
  `offset_sched_time` varchar(60) NOT NULL,
  `reson` longtext NOT NULL,
  `prepaired_by` varchar(60) NOT NULL,
  `submit_date` timestamp(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_form`
--

CREATE TABLE `request_form` (
  `id` int(11) NOT NULL,
  `employee_number` varchar(60) NOT NULL,
  `type` varchar(60) NOT NULL,
  `reason` varchar(60) NOT NULL,
  `prepared_by` int(11) NOT NULL,
  `submit_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_number` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee','super_admin') DEFAULT 'employee',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_number`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'EMP001', 'admin@btech.com', 'af6fa088505623cec26868adcc4767f6', 'super_admin', 'active', '2026-02-03 07:20:04'),
(4, 'BPC-2019-00525452', 'ariellabuson08@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'employee', 'active', '2026-02-04 19:02:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_number` (`employee_number`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_date` (`announcement_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_document` (`employee_number`,`document_type`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offset`
--
ALTER TABLE `offset`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `offset`
--
ALTER TABLE `offset`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
