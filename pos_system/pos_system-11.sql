-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2026 at 05:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
--
-- MULTI-ROLE SUPPORT UPDATE:
-- users.role changed from ENUM to VARCHAR(255) to support
-- comma-separated roles (e.g., "admin,finance")

CREATE DATABASE IF NOT EXISTS `pos_system`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `pos_system`;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pos_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 19:43:53'),
(2, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 19:44:02'),
(3, 1, 'admin', 'add_user', 'Added user: Russe (cashier) (cashier)', '::1', '2026-09-21 20:34:18'),
(4, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 20:34:25'),
(5, 13, 'Russe (cashier)', 'login', 'User logged in', '::1', '2026-09-21 20:34:28'),
(6, 13, 'Russe (cashier)', 'add_customer', 'Added customer: Tony Stark', '::1', '2026-09-21 20:35:28'),
(7, 13, 'Russe (cashier)', 'add_customer', 'Added customer: Barrack Obama', '::1', '2026-09-21 20:36:04'),
(8, 13, 'Russe (cashier)', 'add_customer', 'Added customer: Peter Parker', '::1', '2026-09-21 20:36:45'),
(9, 13, 'Russe (cashier)', 'add_customer', 'Added customer: Edward Norton', '::1', '2026-09-21 20:37:54'),
(10, 13, 'Russe (cashier)', 'add_customer', 'Added customer: Ishow Speed', '::1', '2026-09-21 20:38:29'),
(11, 13, 'Russe (cashier)', 'add_customer', 'Added customer: Kai Cenat', '::1', '2026-09-21 20:38:51'),
(12, 13, 'Russe (cashier)', 'add_customer', 'Added customer: Mr. Beast', '::1', '2026-09-21 20:39:54'),
(13, 13, 'Russe (cashier)', 'add_customer', 'Added customer: Rodrigo Duterte', '::1', '2026-09-21 20:40:51'),
(14, 13, 'Russe (cashier)', 'add_loyalty_points', 'Added 5 loyalty points to customer ID: 7', '::1', '2026-09-21 20:42:48'),
(15, 13, 'Russe (cashier)', 'create_sale', 'Created sale: SM-20260921-5161 (Total: 520.8, VAT: 55.8, Discount: 0, Loyalty: 0)', '::1', '2026-09-21 20:42:48'),
(16, 13, 'Russe (cashier)', 'add_loyalty_points', 'Added 1 loyalty points to customer ID: 5', '::1', '2026-09-21 20:45:53'),
(17, 13, 'Russe (cashier)', 'create_sale', 'Created sale: SM-20260921-4926 (Total: 193.76, VAT: 20.76, Discount: 0, Loyalty: 0)', '::1', '2026-09-21 20:45:53'),
(18, 13, 'Russe (cashier)', 'create_sale', 'Created sale: SM-20260921-1809 (Total: 50.4, VAT: 5.4, Discount: 0, Loyalty: 0)', '::1', '2026-09-21 20:47:43'),
(19, 13, 'Russe (cashier)', 'logout', 'User logged out', '::1', '2026-09-21 20:48:01'),
(20, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 20:48:04'),
(21, 1, 'admin', 'archive_product', 'Archived product: Absolute Distilled Water 500ml (ID: 12)', '::1', '2026-09-21 20:49:48'),
(22, 1, 'admin', 'add_user', 'Added user: kurt (inventory) (inventory)', '::1', '2026-09-21 20:51:11'),
(23, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 20:51:18'),
(24, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 20:51:21'),
(25, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 20:53:17'),
(26, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 20:53:19'),
(27, 1, 'admin', 'restore_product', 'Restored product: Absolute Distilled Water 500ml (ID: 12)', '::1', '2026-09-21 20:53:25'),
(28, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 20:53:28'),
(29, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 20:53:33'),
(30, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 21:09:54'),
(31, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 21:09:57'),
(32, 1, 'admin', 'add_user', 'Added user: Yuki (finance) (finance)', '::1', '2026-09-21 21:15:49'),
(33, 1, 'admin', 'add_user', 'Added user: Faith (hr) (hr)', '::1', '2026-09-21 21:16:56'),
(34, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 21:17:01'),
(35, 16, 'Faith (hr)', 'login', 'User logged in', '::1', '2026-09-21 21:17:04'),
(36, 16, 'Faith (hr)', 'auto_sync_employee', 'Auto-created employee record for user: admin (admin)', '::1', '2026-09-21 21:17:21'),
(37, 16, 'Faith (hr)', 'auto_sync_employee', 'Auto-created employee record for user: Russe (cashier) (cashier)', '::1', '2026-09-21 21:17:21'),
(38, 16, 'Faith (hr)', 'auto_sync_employee', 'Auto-created employee record for user: kurt (inventory) (inventory)', '::1', '2026-09-21 21:17:21'),
(39, 16, 'Faith (hr)', 'auto_sync_employee', 'Auto-created employee record for user: Yuki (finance) (finance)', '::1', '2026-09-21 21:17:21'),
(40, 16, 'Faith (hr)', 'auto_sync_employee', 'Auto-created employee record for user: Faith (hr) (hr)', '::1', '2026-09-21 21:17:21'),
(41, 16, 'Faith (hr)', 'logout', 'User logged out', '::1', '2026-09-21 21:52:47'),
(42, 13, 'Russe (cashier)', 'login', 'User logged in', '::1', '2026-09-21 21:52:49'),
(43, 13, 'Russe (cashier)', 'logout', 'User logged out', '::1', '2026-09-21 21:53:00'),
(44, 15, 'Yuki (finance)', 'login', 'User logged in', '::1', '2026-09-21 21:53:04'),
(45, 15, 'Yuki (finance)', 'logout', 'User logged out', '::1', '2026-09-21 22:10:51'),
(46, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 22:10:57'),
(47, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 22:56:56'),
(48, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 22:57:00'),
(49, 1, 'admin', 'adjust_stock', 'Adjusted stock for product ID 41: -2 (damage)', '::1', '2026-09-21 22:57:43'),
(50, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 22:57:48'),
(51, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 22:57:54'),
(52, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 22:58:15'),
(53, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 22:58:18'),
(54, 1, 'admin', 'adjust_stock', 'Adjusted stock for product ID 18: -1 (other)', '::1', '2026-09-21 22:58:48'),
(55, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 22:58:52'),
(56, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 22:58:56'),
(57, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 22:59:36'),
(58, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 22:59:39'),
(59, 1, 'admin', 'archive_product', 'Archived product: Absolute Distilled Water 500ml (ID: 12)', '::1', '2026-09-21 22:59:49'),
(60, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 22:59:52'),
(61, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 22:59:56'),
(62, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 23:08:37'),
(63, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 23:08:42'),
(64, 1, 'admin', 'restore_product', 'Restored product: Absolute Distilled Water 500ml (ID: 12)', '::1', '2026-09-21 23:08:47'),
(65, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 23:08:50'),
(66, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 23:08:54'),
(67, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 23:09:17'),
(68, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 23:09:20'),
(69, 1, 'admin', 'archive_product', 'Archived product: Argentina Meat Loaf 150g (ID: 50)', '::1', '2026-09-21 23:09:32'),
(70, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 23:09:35'),
(71, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 23:09:42'),
(72, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 23:18:29'),
(73, 1, 'admin', 'login', 'User logged in', '::1', '2026-09-21 23:18:32'),
(74, 1, 'admin', 'restore_product', 'Restored product: Argentina Meat Loaf 150g (ID: 50)', '::1', '2026-09-21 23:18:39'),
(75, 1, 'admin', 'logout', 'User logged out', '::1', '2026-09-21 23:18:41'),
(76, 14, 'kurt (inventory)', 'login', 'User logged in', '::1', '2026-09-21 23:18:44'),
(77, 14, 'kurt (inventory)', 'logout', 'User logged out', '::1', '2026-09-21 23:21:48'),
(78, 13, 'Russe (cashier)', 'login', 'User logged in', '::1', '2026-09-21 23:21:52'),
(79, 13, 'Russe (cashier)', 'create_return', 'Created return: RET-20260921-3452 for sale: 3 (Total Refund: ₱45)', '::1', '2026-09-21 23:22:37'),
(80, 13, 'Russe (cashier)', 'logout', 'User logged out', '::1', '2026-09-21 23:22:43'),
(81, 15, 'Yuki (finance)', 'login', 'User logged in', '::1', '2026-09-21 23:22:49'),
(82, 15, 'Yuki (finance)', 'reject_return', 'Rejected return ID: 1', '::1', '2026-09-21 23:30:36'),
(83, 15, 'Yuki (finance)', 'logout', 'User logged out', '::1', '2026-09-21 23:34:11'),
(84, 16, 'Faith (hr)', 'login', 'User logged in', '::1', '2026-09-21 23:34:13'),
(85, 16, 'Faith (hr)', 'clock_in', 'Employee clocked in (ID: 3)', '::1', '2026-09-21 23:34:37'),
(86, 16, 'Faith (hr)', 'clock_in', 'Employee clocked in (ID: 1)', '::1', '2026-09-21 23:35:00'),
(87, 16, 'Faith (hr)', 'clock_in', 'Employee clocked in (ID: 2)', '::1', '2026-09-21 23:35:06'),
(88, 16, 'Faith (hr)', 'clock_in', 'Employee clocked in (ID: 4)', '::1', '2026-09-21 23:35:16'),
(89, 16, 'Faith (hr)', 'clock_in', 'Employee clocked in (ID: 5)', '::1', '2026-09-21 23:35:38'),
(90, 16, 'Faith (hr)', 'logout', 'User logged out', '::1', '2026-09-21 23:36:27'),
(91, 15, 'Yuki (finance)', 'login', 'User logged in', '::1', '2026-09-21 23:36:31'),
(92, 15, 'Yuki (finance)', 'save_expense', 'Recorded expense: Marketing - ₱67', '::1', '2026-09-21 23:38:39'),
(93, 15, 'Yuki (finance)', 'logout', 'User logged out', '::1', '2026-09-21 23:38:54');

-- --------------------------------------------------------

--
-- Table structure for table `archive_history`
--

CREATE TABLE `archive_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `action` varchar(20) DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archived_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `restored_by` int(11) DEFAULT NULL,
  `restored_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archive_history`
--

INSERT INTO `archive_history` (`id`, `product_id`, `product_name`, `action`, `archived_by`, `archived_date`, `restored_by`, `restored_date`) VALUES
(1, 12, 'Absolute Distilled Water 500ml', 'archived', 1, '2026-09-21 12:49:48', NULL, NULL),
(2, 12, 'Absolute Distilled Water 500ml', 'restored', NULL, '2026-09-21 12:53:25', 1, '2026-09-21 12:53:25'),
(3, 12, 'Absolute Distilled Water 500ml', 'archived', 1, '2026-09-21 14:59:49', NULL, NULL),
(4, 12, 'Absolute Distilled Water 500ml', 'restored', NULL, '2026-09-21 15:08:47', 1, '2026-09-21 15:08:47'),
(5, 50, 'Argentina Meat Loaf 150g', 'archived', 1, '2026-09-21 15:09:32', NULL, NULL),
(6, 50, 'Argentina Meat Loaf 150g', 'restored', NULL, '2026-09-21 15:18:39', 1, '2026-09-21 15:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT 0.00,
  `status` enum('present','absent','late','half_day','holiday') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `date`, `time_in`, `time_out`, `hours_worked`, `status`, `notes`, `created_at`) VALUES
(1, 3, '2026-09-21', '2026-09-21 23:34:37', NULL, 0.00, 'present', NULL, '2026-09-21 15:34:37'),
(2, 1, '2026-09-21', '2026-09-21 23:35:00', NULL, 0.00, 'present', NULL, '2026-09-21 15:35:00'),
(3, 2, '2026-09-21', '2026-09-21 23:35:06', NULL, 0.00, 'present', NULL, '2026-09-21 15:35:06'),
(4, 4, '2026-09-21', '2026-09-21 23:35:16', NULL, 0.00, 'present', NULL, '2026-09-21 15:35:16'),
(5, 5, '2026-09-21', '2026-09-21 23:35:38', NULL, 0.00, 'present', NULL, '2026-09-21 15:35:38');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_hours`
--

CREATE TABLE `attendance_hours` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `hours_worked` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Beverages', 'Soft drinks, juices, water, and other drinks', '2026-09-21 11:49:58'),
(2, 'Snacks', 'Chips, biscuits, candies, and quick bites', '2026-09-21 11:49:58'),
(3, 'Canned Goods', 'Canned meat, fish, vegetables, and fruits', '2026-09-21 11:49:58'),
(4, 'Personal Care', 'Soap, shampoo, toothpaste, and hygiene products', '2026-09-21 11:49:58'),
(5, 'Household', 'Cleaning supplies, detergents, and home essentials', '2026-09-21 11:49:58'),
(6, 'School Supplies', 'Notebooks, pens, paper, and school items', '2026-09-21 11:49:58'),
(7, 'Frozen Foods', 'Frozen meat, seafood, and ready-to-cook items', '2026-09-21 11:49:58'),
(8, 'Condiments', 'Sauces, spices, seasonings, and cooking essentials', '2026-09-21 11:49:58');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `loyalty_points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `created_at`, `loyalty_points`) VALUES
(1, 'Tony Stark', 'tony@gmail.com', '09293569768', '2026-09-21 12:35:28', 0),
(2, 'Barrack Obama', 'obama@gmail.com', '00956869346', '2026-09-21 12:36:04', 0),
(3, 'Peter Parker', 'parker@gmail.com', '09867453524', '2026-09-21 12:36:45', 0),
(4, 'Edward Norton', 'edward.ncst@gmail.com', '09133694756', '2026-09-21 12:37:54', 0),
(5, 'Ishow Speed', 'speed@gmail.com', '09568456356', '2026-09-21 12:38:29', 1),
(6, 'Kai Cenat', 'cenat@gmail.com', '09457644478', '2026-09-21 12:38:51', 0),
(7, 'Mr. Beast', 'donald@gmail.com', '09456785687', '2026-09-21 12:39:54', 5),
(8, 'Rodrigo Duterte', 'duterte@gmail.com', '09123456788', '2026-09-21 12:40:51', 0);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT 0.00,
  `salary_type` enum('monthly','semi_monthly','daily','hourly') DEFAULT 'monthly',
  `status` enum('active','inactive','terminated') DEFAULT 'active',
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `total_days_worked` int(11) DEFAULT 0,
  `shift_count` int(11) DEFAULT 0,
  `daily_rate` decimal(10,2) DEFAULT 0.00,
  `hourly_rate` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `position`, `department`, `hire_date`, `salary`, `salary_type`, `status`, `emergency_contact_name`, `emergency_contact_phone`, `created_at`, `updated_at`, `role`, `user_id`, `start_date`, `contract_end_date`, `total_days_worked`, `shift_count`, `daily_rate`, `hourly_rate`) VALUES
(1, 'USR-1', 'Administrator', '', NULL, NULL, NULL, 'Administrator', 'Management', NULL, 0.00, 'monthly', 'active', NULL, NULL, '2026-09-21 13:17:21', '2026-09-21 13:17:21', 'admin', NULL, '2026-09-21', NULL, 0, 0, 0.00, 0.00),
(2, 'USR-13', 'Russel', 'Infanso', NULL, NULL, NULL, 'Cashier', 'Store Operations', NULL, 0.00, 'monthly', 'active', NULL, NULL, '2026-09-21 13:17:21', '2026-09-21 13:17:21', 'cashier', NULL, '2026-09-21', NULL, 0, 0, 0.00, 0.00),
(3, 'USR-14', 'Kurt', 'Mendoza', NULL, NULL, NULL, 'Inventory Staff', 'Inventory', NULL, 0.00, 'monthly', 'active', NULL, NULL, '2026-09-21 13:17:21', '2026-09-21 13:17:21', 'inventory', NULL, '2026-09-21', NULL, 0, 0, 0.00, 0.00),
(4, 'USR-15', 'Yuki', 'Hattori', NULL, NULL, NULL, 'Finance Staff', 'Finance', NULL, 0.00, 'monthly', 'active', NULL, NULL, '2026-09-21 13:17:21', '2026-09-21 13:17:21', 'finance', NULL, '2026-09-21', NULL, 0, 0, 0.00, 0.00),
(5, 'USR-16', 'Faith', 'Cabrera', NULL, NULL, NULL, 'HR Staff', 'Human Resources', NULL, 0.00, 'monthly', 'active', NULL, NULL, '2026-09-21 13:17:21', '2026-09-21 13:17:21', 'hr', NULL, '2026-09-21', NULL, 0, 0, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_date`, `category`, `description`, `amount`, `payment_method`, `recorded_by`, `created_at`) VALUES
(1, '2026-09-21', 'Marketing', 'boosting sa fb ', 67.00, 'cash', 15, '2026-09-21 15:38:39');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_notifications`
--

CREATE TABLE `inventory_notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'product_added, product_edited, product_deleted, stock_added, stock_adjusted, price_changed',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'sale_id, purchase_id, or log id',
  `triggered_by` int(11) NOT NULL COMMENT 'user who made the change',
  `triggered_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_notifications`
--

INSERT INTO `inventory_notifications` (`id`, `type`, `title`, `message`, `product_id`, `reference_id`, `triggered_by`, `triggered_by_name`, `created_at`) VALUES
(1, 'stock_adjusted', '⚙️ Stock Adjusted', 'Stock for \"555 Sardines in Tomato Sauce 155g\" decreased by 2. Reason: damage. By Administrator.', 41, 598, 1, 'Administrator', '2026-09-21 14:57:43'),
(2, 'stock_adjusted', '⚙️ Stock Adjusted', 'Stock for \"Alaska Evaporated Milk 370ml\" decreased by 1. Reason: other. By Administrator.', 18, 349, 1, 'Administrator', '2026-09-21 14:58:48'),
(3, 'product_archived', '📁 Product Archived', 'Product \"Absolute Distilled Water 500ml\" was archived by Administrator.', 12, NULL, 1, 'Administrator', '2026-09-21 14:59:49'),
(4, 'product_restored', '♻️ Product Restored', 'Product \"Absolute Distilled Water 500ml\" was restored by Administrator.', 12, NULL, 1, 'Administrator', '2026-09-21 15:08:47'),
(5, 'product_archived', '📁 Product Archived', 'Product \"Argentina Meat Loaf 150g\" was archived by Administrator.', 50, NULL, 1, 'Administrator', '2026-09-21 15:09:32'),
(6, 'product_restored', '♻️ Product Restored', 'Product \"Argentina Meat Loaf 150g\" was restored by Administrator.', 50, NULL, 1, 'Administrator', '2026-09-21 15:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('vacation','sick','emergency','maternity','paternity','other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` int(11) NOT NULL DEFAULT 0,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `attempted_at`) VALUES
(1, 'cashier', '::1', '2026-09-21 19:43:56');

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_reads`
--

INSERT INTO `notification_reads` (`id`, `notification_id`, `user_id`, `read_at`) VALUES
(1, 1, 14, '2026-09-21 14:59:16'),
(2, 2, 14, '2026-09-21 14:59:16'),
(4, 3, 14, '2026-09-21 15:00:02'),
(6, 4, 14, '2026-09-21 15:09:00'),
(7, 5, 14, '2026-09-21 15:09:46'),
(9, 6, 14, '2026-09-21 15:18:48');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `payroll_type` enum('monthly','daily','hourly') DEFAULT 'monthly',
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_notifications`
--

CREATE TABLE `payroll_notifications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `notification_type` enum('email','system') DEFAULT 'system',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `barcode` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived` tinyint(1) DEFAULT 0,
  `unit` varchar(20) NOT NULL DEFAULT 'pc',
  `sku` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `low_stock_threshold` int(11) DEFAULT 5,
  `default_supplier_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `description`, `image`, `selling_price`, `cost_price`, `stock_quantity`, `barcode`, `created_at`, `archived`, `unit`, `sku`, `active`, `low_stock_threshold`, `default_supplier_id`) VALUES
(1, 'Coca-Cola 1.5L', 1, NULL, NULL, 75.00, 60.00, 325, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-001', 1, 10, 1),
(2, 'Pepsi 1.5L', 1, NULL, NULL, 72.00, 58.00, 375, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-002', 1, 10, 1),
(3, 'Sprite 1.5L', 1, NULL, NULL, 72.00, 58.00, 375, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-003', 1, 10, 1),
(4, 'Royal Tru-Orange 1.5L', 1, NULL, NULL, 75.00, 60.00, 330, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-004', 1, 10, 1),
(5, 'Mountain Dew 1.5L', 1, NULL, NULL, 78.00, 62.00, 330, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-005', 1, 10, 1),
(6, 'Coke Zero 1.5L', 1, NULL, NULL, 78.00, 62.00, 260, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-006', 1, 10, 1),
(7, 'C2 Green Tea 500ml', 1, NULL, NULL, 30.00, 22.00, 370, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-007', 1, 15, 1),
(8, 'Del Monte Pineapple Juice 240ml', 1, NULL, NULL, 25.00, 18.00, 380, NULL, '2026-09-21 11:49:58', 0, 'can', 'BEV-008', 1, 20, 1),
(9, 'Minute Maid Orange 1L', 1, NULL, NULL, 85.00, 68.00, 310, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-009', 1, 10, 1),
(10, 'Nestea Iced Tea 1L', 1, NULL, NULL, 65.00, 50.00, 330, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-010', 1, 10, 1),
(11, 'Wilkins Distilled Water 500ml', 1, NULL, NULL, 18.00, 12.00, 450, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-011', 1, 30, 1),
(12, 'Absolute Distilled Water 500ml', 1, NULL, NULL, 18.00, 12.00, 449, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-012', 1, 30, 1),
(13, 'Summit Water 500ml', 1, NULL, NULL, 15.00, 10.00, 500, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-013', 1, 30, 1),
(14, 'Red Bull 250ml', 1, NULL, NULL, 95.00, 78.00, 300, NULL, '2026-09-21 11:49:58', 0, 'can', 'BEV-014', 1, 10, 1),
(15, 'Sting Energy Drink 330ml', 1, NULL, NULL, 28.00, 20.00, 370, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'BEV-015', 1, 20, 1),
(16, 'Milo Ready-to-Drink 240ml', 1, NULL, NULL, 30.00, 22.00, 400, NULL, '2026-09-21 11:49:58', 0, 'can', 'BEV-016', 1, 20, 1),
(17, 'Bear Brand Sterilized Milk 300ml', 1, NULL, NULL, 45.00, 35.00, 349, NULL, '2026-09-21 11:49:58', 0, 'can', 'BEV-017', 1, 15, 1),
(18, 'Alaska Evaporated Milk 370ml', 1, NULL, NULL, 42.00, 32.00, 349, NULL, '2026-09-21 11:49:58', 0, 'can', 'BEV-018', 1, 15, 1),
(19, 'Nescafe 3-in-1 Original', 1, NULL, NULL, 12.00, 8.00, 550, NULL, '2026-09-21 11:49:58', 0, 'pack', 'BEV-019', 1, 50, 1),
(20, 'Kopiko Black 3-in-1', 1, NULL, NULL, 10.00, 7.00, 550, NULL, '2026-09-21 11:49:58', 0, 'pack', 'BEV-020', 1, 50, 1),
(21, 'Piattos Cheese 85g', 2, NULL, NULL, 32.00, 24.00, 450, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-001', 1, 20, 4),
(22, 'Piattos Sour Cream 85g', 2, NULL, NULL, 32.00, 24.00, 450, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-002', 1, 20, 4),
(23, 'Nova Multigrain 78g', 2, NULL, NULL, 28.00, 20.00, 450, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-003', 1, 20, 4),
(24, 'Chippy BBQ 110g', 2, NULL, NULL, 30.00, 22.00, 450, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-004', 1, 20, 4),
(25, 'V-Cut BBQ 85g', 2, NULL, NULL, 32.00, 24.00, 420, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-005', 1, 20, 4),
(26, 'Doritos Nacho Cheese 90g', 2, NULL, NULL, 45.00, 35.00, 380, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-006', 1, 15, 4),
(27, 'Cheetos Crunchy 80g', 2, NULL, NULL, 38.00, 28.00, 380, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-007', 1, 15, 4),
(28, 'Pringles Original 107g', 2, NULL, NULL, 115.00, 90.00, 340, NULL, '2026-09-21 11:49:58', 0, 'can', 'SNK-008', 1, 10, 4),
(29, 'Oreo Original 133g', 2, NULL, NULL, 55.00, 42.00, 380, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-009', 1, 15, 4),
(30, 'Cream-O Vanilla 133g', 2, NULL, NULL, 42.00, 32.00, 420, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-010', 1, 15, 4),
(31, 'Rebisco Crackers 200g', 2, NULL, NULL, 35.00, 26.00, 470, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-011', 1, 15, 4),
(32, 'Skyflakes Crackers 25g', 2, NULL, NULL, 8.00, 5.00, 700, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-012', 1, 50, 4),
(33, 'Fita Crackers 300g', 2, NULL, NULL, 48.00, 36.00, 500, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-013', 1, 15, 4),
(34, 'Hansel Mocha Sandwich 10s', 2, NULL, NULL, 55.00, 42.00, 430, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-014', 1, 10, 4),
(35, 'Choco Mucho 10s', 2, NULL, NULL, 65.00, 50.00, 380, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-015', 1, 10, 4),
(36, 'Snickers 51g', 2, NULL, NULL, 45.00, 35.00, 380, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SNK-016', 1, 15, 4),
(37, 'M&M\'s Milk Chocolate 40g', 2, NULL, NULL, 55.00, 42.00, 360, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-017', 1, 10, 4),
(38, 'Kinder Bueno 43g', 2, NULL, NULL, 65.00, 50.00, 340, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SNK-018', 1, 10, 4),
(39, 'Hello Panda Chocolate 40g', 2, NULL, NULL, 32.00, 24.00, 400, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-019', 1, 15, 4),
(40, 'Pocky Chocolate 47g', 2, NULL, NULL, 55.00, 42.00, 360, NULL, '2026-09-21 11:49:58', 0, 'pack', 'SNK-020', 1, 10, 4),
(41, '555 Sardines in Tomato Sauce 155g', 3, NULL, NULL, 25.00, 18.00, 598, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-001', 1, 30, 7),
(42, 'Ligo Sardines Red 155g', 3, NULL, NULL, 25.00, 18.00, 400, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-002', 1, 30, 7),
(43, 'Mega Sardines 155g', 3, NULL, NULL, 24.00, 17.00, 600, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-003', 1, 30, 7),
(44, 'Century Tuna Flakes in Oil 155g', 3, NULL, NULL, 42.00, 32.00, 550, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-004', 1, 20, 7),
(45, 'Century Tuna Hot & Spicy 155g', 3, NULL, NULL, 42.00, 32.00, 550, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-005', 1, 20, 7),
(46, 'Argentina Corned Beef 150g', 3, NULL, NULL, 48.00, 36.00, 470, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-006', 1, 15, 7),
(47, 'Purefoods Corned Beef 150g', 3, NULL, NULL, 55.00, 42.00, 500, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-007', 1, 15, 7),
(48, 'Highlands Corned Beef 150g', 3, NULL, NULL, 52.00, 40.00, 500, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-008', 1, 15, 7),
(49, 'Libby\'s Corned Beef 150g', 3, NULL, NULL, 58.00, 45.00, 480, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-009', 1, 10, 7),
(50, 'Argentina Meat Loaf 150g', 3, NULL, NULL, 35.00, 26.00, 320, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-010', 1, 15, 7),
(51, 'Maling Luncheon Meat 397g', 3, NULL, NULL, 95.00, 75.00, 280, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-011', 1, 10, 7),
(52, 'Spam Classic 340g', 3, NULL, NULL, 185.00, 150.00, 250, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-012', 1, 10, 7),
(53, 'Del Monte Tomato Sauce 250g', 3, NULL, NULL, 32.00, 24.00, 450, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-013', 1, 15, 7),
(54, 'Del Monte Tomato Paste 150g', 3, NULL, NULL, 35.00, 26.00, 430, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-014', 1, 15, 7),
(55, 'Hunt\'s Pork & Beans 400g', 3, NULL, NULL, 55.00, 42.00, 280, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-015', 1, 10, 7),
(56, 'Del Monte Whole Kernel Corn 400g', 3, NULL, NULL, 65.00, 50.00, 410, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-016', 1, 10, 7),
(57, 'Del Monte Fruit Cocktail 822g', 3, NULL, NULL, 145.00, 115.00, 380, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-017', 1, 10, 7),
(58, 'Del Monte Pineapple Chunks 822g', 3, NULL, NULL, 145.00, 115.00, 380, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-018', 1, 10, 7),
(59, 'Jolly Mushroom Pieces & Stems 400g', 3, NULL, NULL, 65.00, 50.00, 280, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-019', 1, 10, 7),
(60, 'Angel Evaporada 370ml', 3, NULL, NULL, 38.00, 28.00, 300, NULL, '2026-09-21 11:49:58', 0, 'can', 'CAN-020', 1, 15, 7),
(61, 'Safeguard Pure White Soap 130g', 4, NULL, NULL, 45.00, 34.00, 350, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-001', 1, 20, 10),
(62, 'Safeguard Charcoal Soap 130g', 4, NULL, NULL, 45.00, 34.00, 350, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-002', 1, 20, 10),
(63, 'Dove Beauty Bar 100g', 4, NULL, NULL, 85.00, 68.00, 300, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-003', 1, 15, 10),
(64, 'Palmolive Naturals Shampoo 180ml', 4, NULL, NULL, 95.00, 75.00, 330, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'PER-004', 1, 10, 10),
(65, 'Head & Shoulders Shampoo 180ml', 4, NULL, NULL, 145.00, 115.00, 309, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'PER-005', 1, 10, 10),
(66, 'Sunsilk Shampoo 180ml', 4, NULL, NULL, 95.00, 75.00, 280, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'PER-006', 1, 10, 10),
(67, 'Pantene Shampoo 180ml', 4, NULL, NULL, 155.00, 125.00, 310, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'PER-007', 1, 10, 10),
(68, 'Colgate Toothpaste 145g', 4, NULL, NULL, 95.00, 75.00, 350, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-008', 1, 15, 10),
(69, 'Crest Toothpaste 120g', 4, NULL, NULL, 85.00, 68.00, 180, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-009', 1, 10, 10),
(70, 'Close-Up Toothpaste 140g', 4, NULL, NULL, 78.00, 62.00, 280, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-010', 1, 10, 10),
(71, 'Oral-B Toothbrush', 4, NULL, NULL, 55.00, 42.00, 350, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-011', 1, 15, 10),
(72, 'Colgate Toothbrush', 4, NULL, NULL, 45.00, 35.00, 350, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-012', 1, 15, 10),
(73, 'Rexona Deodorant Spray 150ml', 4, NULL, NULL, 145.00, 115.00, 260, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'PER-013', 1, 10, 10),
(74, 'Nivea Deodorant Spray 150ml', 4, NULL, NULL, 165.00, 135.00, 160, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'PER-014', 1, 10, 10),
(75, 'Gillette Shaving Cream 100g', 4, NULL, NULL, 125.00, 100.00, 150, NULL, '2026-09-21 11:49:58', 0, 'can', 'PER-015', 1, 10, 10),
(76, 'Ponds Facial Wash 100g', 4, NULL, NULL, 135.00, 108.00, 260, NULL, '2026-09-21 11:49:58', 0, 'pc', 'PER-016', 1, 10, 10),
(77, 'Nivea Body Lotion 200ml', 4, NULL, NULL, 185.00, 150.00, 150, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'PER-017', 1, 10, 10),
(78, 'Johnson\'s Baby Powder 200g', 4, NULL, NULL, 125.00, 100.00, 160, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'PER-018', 1, 10, 10),
(79, 'Cotton Buds 200pcs', 4, NULL, NULL, 35.00, 26.00, 200, NULL, '2026-09-21 11:49:58', 0, 'pack', 'PER-019', 1, 15, 10),
(80, 'Face Mask 3-Ply (50pcs)', 4, NULL, NULL, 95.00, 75.00, 180, NULL, '2026-09-21 11:49:58', 0, 'box', 'PER-020', 1, 10, 10),
(81, 'Tide Powder Detergent 1kg', 5, NULL, NULL, 145.00, 115.00, 310, NULL, '2026-09-21 11:49:58', 0, 'pack', 'HOU-001', 1, 10, 14),
(82, 'Ariel Powder Detergent 1kg', 5, NULL, NULL, 155.00, 125.00, 309, NULL, '2026-09-21 11:49:58', 0, 'pack', 'HOU-002', 1, 10, 14),
(83, 'Surf Powder Detergent 1kg', 5, NULL, NULL, 125.00, 100.00, 160, NULL, '2026-09-21 11:49:58', 0, 'pack', 'HOU-003', 1, 10, 14),
(84, 'Downy Fabric Conditioner 1L', 5, NULL, NULL, 195.00, 160.00, 290, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'HOU-004', 1, 10, 14),
(85, 'Comfort Fabric Conditioner 1L', 5, NULL, NULL, 185.00, 150.00, 140, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'HOU-005', 1, 10, 14),
(86, 'Joy Dishwashing Liquid 500ml', 5, NULL, NULL, 85.00, 68.00, 330, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'HOU-006', 1, 15, 14),
(87, 'Axion Dishwashing Paste 200g', 5, NULL, NULL, 65.00, 50.00, 180, NULL, '2026-09-21 11:49:58', 0, 'pc', 'HOU-007', 1, 15, 14),
(88, 'Smart Dishwashing Paste 200g', 5, NULL, NULL, 55.00, 42.00, 180, NULL, '2026-09-21 11:49:58', 0, 'pc', 'HOU-008', 1, 15, 14),
(89, 'Zonrox Bleach 1L', 5, NULL, NULL, 78.00, 62.00, 310, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'HOU-009', 1, 10, 14),
(90, 'Clorox Bleach 1L', 5, NULL, NULL, 95.00, 75.00, 140, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'HOU-010', 1, 10, 14),
(91, 'Lysol Disinfectant Spray 340g', 5, NULL, NULL, 245.00, 200.00, 120, NULL, '2026-09-21 11:49:58', 0, 'can', 'HOU-011', 1, 10, 14),
(92, 'Glade Air Freshener 300ml', 5, NULL, NULL, 155.00, 125.00, 270, NULL, '2026-09-21 11:49:58', 0, 'can', 'HOU-012', 1, 10, 14),
(93, 'Scotch Brite Scouring Pad', 5, NULL, NULL, 35.00, 26.00, 200, NULL, '2026-09-21 11:49:58', 0, 'pack', 'HOU-013', 1, 20, 14),
(94, 'Trash Bags Medium 20pcs', 5, NULL, NULL, 85.00, 68.00, 160, NULL, '2026-09-21 11:49:58', 0, 'pack', 'HOU-014', 1, 10, 14),
(95, 'Trash Bags Large 10pcs', 5, NULL, NULL, 95.00, 75.00, 140, NULL, '2026-09-21 11:49:58', 0, 'pack', 'HOU-015', 1, 10, 14),
(96, 'Broom Soft Bristles', 5, NULL, NULL, 125.00, 100.00, 130, NULL, '2026-09-21 11:49:58', 0, 'pc', 'HOU-016', 1, 10, 14),
(97, 'Dustpan Plastic', 5, NULL, NULL, 65.00, 50.00, 140, NULL, '2026-09-21 11:49:58', 0, 'pc', 'HOU-017', 1, 10, 14),
(98, 'Mop Head with Handle', 5, NULL, NULL, 185.00, 150.00, 120, NULL, '2026-09-21 11:49:58', 0, 'pc', 'HOU-018', 1, 10, 14),
(99, 'Toilet Bowl Cleaner 500ml', 5, NULL, NULL, 125.00, 100.00, 140, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'HOU-019', 1, 10, 14),
(100, 'Glass Cleaner Spray 500ml', 5, NULL, NULL, 135.00, 108.00, 140, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'HOU-020', 1, 10, 14),
(101, 'Mongol Pencil #2', 6, NULL, NULL, 8.00, 5.00, 800, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-001', 1, 50, 17),
(102, 'Faber-Castell Pencil #2', 6, NULL, NULL, 15.00, 11.00, 600, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-002', 1, 30, 17),
(103, 'Bic Ballpen Blue', 6, NULL, NULL, 12.00, 8.00, 450, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-003', 1, 50, 17),
(104, 'Bic Ballpen Black', 6, NULL, NULL, 12.00, 8.00, 450, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-004', 1, 50, 17),
(105, 'Pilot G-Tec Blue', 6, NULL, NULL, 55.00, 42.00, 250, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-005', 1, 15, 17),
(106, 'Pilot G-Tec Black', 6, NULL, NULL, 55.00, 42.00, 250, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-006', 1, 15, 17),
(107, 'Notebook 80 Leaves', 6, NULL, NULL, 35.00, 26.00, 500, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-007', 1, 30, 17),
(108, 'Notebook 120 Leaves', 6, NULL, NULL, 50.00, 38.00, 450, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-008', 1, 20, 17),
(109, 'Intermediate Pad 80 leaves', 6, NULL, NULL, 45.00, 35.00, 250, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-009', 1, 15, 17),
(110, 'Yellow Pad 80 leaves', 6, NULL, NULL, 55.00, 42.00, 250, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-010', 1, 15, 17),
(111, 'Bond Paper A4 500s', 6, NULL, NULL, 285.00, 240.00, 340, NULL, '2026-09-21 11:49:58', 0, 'ream', 'SCH-011', 1, 10, 17),
(112, 'Bond Paper Short 500s', 6, NULL, NULL, 235.00, 195.00, 340, NULL, '2026-09-21 11:49:58', 0, 'ream', 'SCH-012', 1, 10, 17),
(113, 'Correction Tape 5m', 6, NULL, NULL, 45.00, 35.00, 230, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-013', 1, 15, 17),
(114, 'Correction Fluid 20ml', 6, NULL, NULL, 55.00, 42.00, 210, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'SCH-014', 1, 10, 17),
(115, 'Eraser White', 6, NULL, NULL, 8.00, 5.00, 350, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-015', 1, 30, 17),
(116, 'Pencil Sharpener', 6, NULL, NULL, 15.00, 10.00, 300, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-016', 1, 20, 17),
(117, 'Ruler 12-inch Plastic', 6, NULL, NULL, 25.00, 18.00, 250, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-017', 1, 15, 17),
(118, 'Scissors Small', 6, NULL, NULL, 45.00, 35.00, 210, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-018', 1, 10, 17),
(119, 'Glue Stick 21g', 6, NULL, NULL, 45.00, 35.00, 230, NULL, '2026-09-21 11:49:58', 0, 'pc', 'SCH-019', 1, 15, 17),
(120, 'Crayons 16 Colors', 6, NULL, NULL, 65.00, 50.00, 480, NULL, '2026-09-21 11:49:58', 0, 'box', 'SCH-020', 1, 10, 17),
(121, 'Purefoods Tender Juicy Hotdog 1kg', 7, NULL, NULL, 285.00, 235.00, 210, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-001', 1, 10, 19),
(122, 'CDO Funtastyk Hotdog 1kg', 7, NULL, NULL, 265.00, 220.00, 210, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-002', 1, 10, 19),
(123, 'Purefoods Chicken Nuggets 200g', 7, NULL, NULL, 125.00, 100.00, 220, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-003', 1, 10, 19),
(124, 'CDO Chicken Nuggets 200g', 7, NULL, NULL, 115.00, 92.00, 220, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-004', 1, 10, 19),
(125, 'Purefoods Corned Beef 150g (Frozen)', 7, NULL, NULL, 55.00, 42.00, 330, NULL, '2026-09-21 11:49:58', 0, 'pc', 'FRZ-005', 1, 10, 19),
(126, 'Magnolia Chicken Whole 1kg', 7, NULL, NULL, 215.00, 180.00, 140, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-006', 1, 10, 19),
(127, 'Magnolia Chicken Breast 500g', 7, NULL, NULL, 145.00, 120.00, 150, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-007', 1, 10, 19),
(128, 'Magnolia Chicken Wings 500g', 7, NULL, NULL, 135.00, 110.00, 150, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-008', 1, 10, 19),
(129, 'Monterey Pork Belly 500g', 7, NULL, NULL, 285.00, 235.00, 130, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-009', 1, 10, 19),
(130, 'Monterey Pork Chop 500g', 7, NULL, NULL, 245.00, 200.00, 130, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-010', 1, 10, 19),
(131, 'Beef Ground 500g', 7, NULL, NULL, 265.00, 220.00, 80, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-011', 1, 10, 19),
(132, 'Beef Cubes 500g', 7, NULL, NULL, 285.00, 235.00, 80, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-012', 1, 10, 19),
(133, 'Bangus Boneless 500g', 7, NULL, NULL, 185.00, 150.00, 89, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-013', 1, 10, 19),
(134, 'Galunggong Cleaned 500g', 7, NULL, NULL, 155.00, 125.00, 90, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-014', 1, 10, 19),
(135, 'Tilapia Whole 500g', 7, NULL, NULL, 135.00, 110.00, 90, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-015', 1, 10, 19),
(136, 'Shrimp Medium 250g', 7, NULL, NULL, 195.00, 160.00, 80, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-016', 1, 10, 19),
(137, 'Squid Rings 250g', 7, NULL, NULL, 155.00, 125.00, 80, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-017', 1, 10, 19),
(138, 'French Fries 500g', 7, NULL, NULL, 125.00, 100.00, 110, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-018', 1, 10, 19),
(139, 'Hash Browns 500g', 7, NULL, NULL, 145.00, 120.00, 90, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-019', 1, 10, 19),
(140, 'Siomai Frozen 20pcs', 7, NULL, NULL, 125.00, 100.00, 110, NULL, '2026-09-21 11:49:58', 0, 'pack', 'FRZ-020', 1, 10, 19),
(141, 'Silver Swan Soy Sauce 385ml', 8, NULL, NULL, 45.00, 35.00, 350, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-001', 1, 20, 22),
(142, 'Silver Swan Vinegar 385ml', 8, NULL, NULL, 42.00, 32.00, 350, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-002', 1, 20, 22),
(143, 'Datu Puti Soy Sauce 385ml', 8, NULL, NULL, 45.00, 35.00, 350, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-003', 1, 20, 22),
(144, 'Datu Puti Vinegar 385ml', 8, NULL, NULL, 42.00, 32.00, 350, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-004', 1, 20, 22),
(145, 'Mang Tomas All-Purpose Sauce 250g', 8, NULL, NULL, 55.00, 42.00, 320, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-005', 1, 15, 22),
(146, 'Mang Tomas Sarsa 250g', 8, NULL, NULL, 55.00, 42.00, 320, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-006', 1, 15, 22),
(147, 'UFC Banana Ketchup 320g', 8, NULL, NULL, 55.00, 42.00, 390, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-007', 1, 15, 22),
(148, 'Del Monte Tomato Ketchup 320g', 8, NULL, NULL, 75.00, 60.00, 330, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-008', 1, 15, 22),
(149, 'Heinz Tomato Ketchup 300g', 8, NULL, NULL, 125.00, 100.00, 160, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-009', 1, 10, 22),
(150, 'Lady\'s Choice Mayonnaise 220ml', 8, NULL, NULL, 85.00, 68.00, 330, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-010', 1, 15, 22),
(151, 'Best Foods Mayonnaise 220ml', 8, NULL, NULL, 135.00, 108.00, 289, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-011', 1, 10, 22),
(152, 'UFC Sweet Chili Sauce 200g', 8, NULL, NULL, 55.00, 42.00, 370, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-012', 1, 15, 22),
(153, 'Maggie Magic Sarap 8g x 10', 8, NULL, NULL, 45.00, 35.00, 250, NULL, '2026-09-21 11:49:58', 0, 'pack', 'CON-013', 1, 20, 22),
(154, 'Aji-Ginisa 8g x 10', 8, NULL, NULL, 42.00, 32.00, 250, NULL, '2026-09-21 11:49:58', 0, 'pack', 'CON-014', 1, 20, 22),
(155, 'Knorr Seasoning 8g x 10', 8, NULL, NULL, 45.00, 35.00, 250, NULL, '2026-09-21 11:49:58', 0, 'pack', 'CON-015', 1, 20, 22),
(156, 'Magic Flakes Seasoning', 8, NULL, NULL, 12.00, 8.00, 300, NULL, '2026-09-21 11:49:58', 0, 'pack', 'CON-016', 1, 30, 22),
(157, 'Del Monte Tomato Sauce 250g', 8, NULL, NULL, 45.00, 35.00, 350, NULL, '2026-09-21 11:49:58', 0, 'pouch', 'CON-017', 1, 15, 22),
(158, 'Clara Ole Pasta Sauce 250g', 8, NULL, NULL, 95.00, 75.00, 160, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-018', 1, 10, 22),
(159, 'Barrio Fiesta Bagoong 250g', 8, NULL, NULL, 75.00, 60.00, 180, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-019', 1, 10, 22),
(160, 'Cooking Oil Palm 1L', 8, NULL, NULL, 125.00, 100.00, 180, NULL, '2026-09-21 11:49:58', 0, 'bottle', 'CON-020', 1, 10, 22);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `po_number`, `supplier_id`, `purchase_date`, `subtotal`, `tax_amount`, `total_amount`, `payment_status`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 'PO-20260921-0001', 1, '2026-09-06', 75400.00, 9048.00, 84448.00, 'paid', 'Beverages initial order', 1, '2026-09-21 12:07:25'),
(2, 'PO-20260921-0002', 4, '2026-09-07', 103950.00, 12474.00, 116424.00, 'paid', 'Snacks weekly restock', 1, '2026-09-21 12:07:25'),
(3, 'PO-20260921-0003', 7, '2026-09-08', 196200.00, 23544.00, 219744.00, 'paid', 'Canned goods delivery', 1, '2026-09-21 12:07:25'),
(4, 'PO-20260921-0004', 10, '2026-09-09', 161700.00, 19404.00, 181104.00, 'paid', 'Personal care monthly stock', 1, '2026-09-21 12:07:25'),
(5, 'PO-20260921-0005', 14, '2026-09-10', 155920.00, 18710.40, 174630.40, 'paid', 'Household cleaning supplies', 1, '2026-09-21 12:07:25'),
(6, 'PO-20260921-0006', 17, '2026-09-11', 138300.00, 16596.00, 154896.00, 'paid', 'School supplies restock', 1, '2026-09-21 12:07:25'),
(7, 'PO-20260921-0007', 19, '2026-09-12', 148950.00, 17874.00, 166824.00, 'paid', 'Frozen foods delivery', 1, '2026-09-21 12:07:25'),
(8, 'PO-20260921-0008', 22, '2026-09-13', 101800.00, 12216.00, 114016.00, 'paid', 'Condiments weekly restock', 1, '2026-09-21 12:07:25'),
(40, 'PO-20260921-PEP', 2, '2026-09-16', 11900.00, 1428.00, 13328.00, 'paid', 'Pepsi-Cola products order', 1, '2026-09-21 12:18:35'),
(41, 'PO-20260921-DEL', 3, '2026-09-16', 26580.00, 3189.60, 29769.60, 'paid', 'Del Monte products', 1, '2026-09-21 12:18:35'),
(42, 'PO-20260921-MON', 5, '2026-09-17', 4100.00, 492.00, 4592.00, 'paid', 'Monde Nissin products', 1, '2026-09-21 12:18:35'),
(43, 'PO-20260921-REB', 6, '2026-09-17', 6800.00, 816.00, 7616.00, 'paid', 'Rebisco products', 1, '2026-09-21 12:18:35'),
(44, 'PO-20260921-PFH', 8, '2026-09-17', 13520.00, 1622.40, 15142.40, 'paid', 'Purefoods Hormel products', 1, '2026-09-21 12:18:35'),
(45, 'PO-20260921-PGP', 11, '2026-09-18', 42480.00, 5097.60, 47577.60, 'paid', 'P&G products', 1, '2026-09-21 12:18:35'),
(46, 'PO-20260921-COL', 12, '2026-09-18', 18160.00, 2179.20, 20339.20, 'paid', 'Colgate-Palmolive products', 1, '2026-09-21 12:18:35'),
(48, 'PO-20260921-ZON', 15, '2026-09-18', 6200.00, 744.00, 6944.00, 'paid', 'Zonrox Bleach order', 1, '2026-09-21 12:18:35'),
(49, 'PO-20260921-MGL', 16, '2026-09-19', 1000.00, 120.00, 1120.00, 'paid', 'Mongol Pencils order', 1, '2026-09-21 12:18:35'),
(50, 'PO-20260921-FAB', 18, '2026-09-19', 6100.00, 732.00, 6832.00, 'paid', 'Faber-Castell products', 1, '2026-09-21 12:18:35'),
(51, 'PO-20260921-CDO', 20, '2026-09-19', 15600.00, 1872.00, 17472.00, 'paid', 'CDO Foodsphere products', 1, '2026-09-21 12:18:35'),
(52, 'PO-20260921-PFF', 21, '2026-09-19', 16750.00, 2010.00, 18760.00, 'paid', 'Purefoods Frozen products', 1, '2026-09-21 12:18:35'),
(53, 'PO-20260921-UFC', 23, '2026-09-19', 6720.00, 806.40, 7526.40, 'paid', 'UFC products', 1, '2026-09-21 12:18:35'),
(54, 'PO-20260921-LAD', 25, '2026-09-19', 10560.00, 1267.20, 11827.20, 'paid', 'Lady\'s Choice & Best Foods products', 1, '2026-09-21 12:18:35'),
(55, 'PO-20260921-MET', 26, '2026-09-20', 18800.00, 2256.00, 21056.00, 'paid', 'Metro Grocery Wholesale order', 1, '2026-09-21 12:18:35'),
(56, 'PO-20260921-PAC', 27, '2026-09-20', 15520.00, 1862.40, 17382.40, 'paid', 'Pacific Bay Trading order', 1, '2026-09-21 12:18:35');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `quantity`, `unit_cost`, `total_cost`) VALUES
(1, 1, 1, 100, 60.00, 6000.00),
(2, 1, 2, 100, 58.00, 5800.00),
(3, 1, 3, 100, 58.00, 5800.00),
(4, 1, 4, 100, 60.00, 6000.00),
(5, 1, 5, 100, 62.00, 6200.00),
(6, 1, 6, 100, 62.00, 6200.00),
(7, 1, 7, 100, 22.00, 2200.00),
(8, 1, 8, 100, 18.00, 1800.00),
(9, 1, 9, 100, 68.00, 6800.00),
(10, 1, 10, 100, 50.00, 5000.00),
(11, 1, 11, 100, 12.00, 1200.00),
(12, 1, 12, 100, 12.00, 1200.00),
(13, 1, 13, 100, 10.00, 1000.00),
(14, 1, 14, 100, 78.00, 7800.00),
(15, 1, 15, 100, 20.00, 2000.00),
(16, 1, 16, 100, 22.00, 2200.00),
(17, 1, 17, 100, 35.00, 3500.00),
(18, 1, 18, 100, 32.00, 3200.00),
(19, 1, 19, 100, 8.00, 800.00),
(20, 1, 20, 100, 7.00, 700.00),
(32, 2, 21, 150, 24.00, 3600.00),
(33, 2, 22, 150, 24.00, 3600.00),
(34, 2, 23, 150, 20.00, 3000.00),
(35, 2, 24, 150, 22.00, 3300.00),
(36, 2, 25, 150, 24.00, 3600.00),
(37, 2, 26, 150, 35.00, 5250.00),
(38, 2, 27, 150, 28.00, 4200.00),
(39, 2, 28, 150, 90.00, 13500.00),
(40, 2, 29, 150, 42.00, 6300.00),
(41, 2, 30, 150, 32.00, 4800.00),
(42, 2, 31, 150, 26.00, 3900.00),
(43, 2, 32, 150, 5.00, 750.00),
(44, 2, 33, 150, 36.00, 5400.00),
(45, 2, 34, 150, 42.00, 6300.00),
(46, 2, 35, 150, 50.00, 7500.00),
(47, 2, 36, 150, 35.00, 5250.00),
(48, 2, 37, 150, 42.00, 6300.00),
(49, 2, 38, 150, 50.00, 7500.00),
(50, 2, 39, 150, 24.00, 3600.00),
(51, 2, 40, 150, 42.00, 6300.00),
(63, 3, 41, 200, 18.00, 3600.00),
(64, 3, 42, 200, 18.00, 3600.00),
(65, 3, 43, 200, 17.00, 3400.00),
(66, 3, 44, 200, 32.00, 6400.00),
(67, 3, 45, 200, 32.00, 6400.00),
(68, 3, 46, 200, 36.00, 7200.00),
(69, 3, 47, 200, 42.00, 8400.00),
(70, 3, 48, 200, 40.00, 8000.00),
(71, 3, 49, 200, 45.00, 9000.00),
(72, 3, 50, 200, 26.00, 5200.00),
(73, 3, 51, 200, 75.00, 15000.00),
(74, 3, 52, 200, 150.00, 30000.00),
(75, 3, 53, 200, 24.00, 4800.00),
(76, 3, 54, 200, 26.00, 5200.00),
(77, 3, 55, 200, 42.00, 8400.00),
(78, 3, 56, 200, 50.00, 10000.00),
(79, 3, 57, 200, 115.00, 23000.00),
(80, 3, 58, 200, 115.00, 23000.00),
(81, 3, 59, 200, 50.00, 10000.00),
(82, 3, 60, 200, 28.00, 5600.00),
(94, 4, 61, 100, 34.00, 3400.00),
(95, 4, 62, 100, 34.00, 3400.00),
(96, 4, 63, 100, 68.00, 6800.00),
(97, 4, 64, 100, 75.00, 7500.00),
(98, 4, 65, 100, 115.00, 11500.00),
(99, 4, 66, 100, 75.00, 7500.00),
(100, 4, 67, 100, 125.00, 12500.00),
(101, 4, 68, 100, 75.00, 7500.00),
(102, 4, 69, 100, 68.00, 6800.00),
(103, 4, 70, 100, 62.00, 6200.00),
(104, 4, 71, 100, 42.00, 4200.00),
(105, 4, 72, 100, 35.00, 3500.00),
(106, 4, 73, 100, 115.00, 11500.00),
(107, 4, 74, 100, 135.00, 13500.00),
(108, 4, 75, 100, 100.00, 10000.00),
(109, 4, 76, 100, 108.00, 10800.00),
(110, 4, 77, 100, 150.00, 15000.00),
(111, 4, 78, 100, 100.00, 10000.00),
(112, 4, 79, 100, 26.00, 2600.00),
(113, 4, 80, 100, 75.00, 7500.00),
(125, 5, 81, 80, 115.00, 9200.00),
(126, 5, 82, 80, 125.00, 10000.00),
(127, 5, 83, 80, 100.00, 8000.00),
(128, 5, 84, 80, 160.00, 12800.00),
(129, 5, 85, 80, 150.00, 12000.00),
(130, 5, 86, 80, 68.00, 5440.00),
(131, 5, 87, 80, 50.00, 4000.00),
(132, 5, 88, 80, 42.00, 3360.00),
(133, 5, 89, 80, 62.00, 4960.00),
(134, 5, 90, 80, 75.00, 6000.00),
(135, 5, 91, 80, 200.00, 16000.00),
(136, 5, 92, 80, 125.00, 10000.00),
(137, 5, 93, 80, 26.00, 2080.00),
(138, 5, 94, 80, 68.00, 5440.00),
(139, 5, 95, 80, 75.00, 6000.00),
(140, 5, 96, 80, 100.00, 8000.00),
(141, 5, 97, 80, 50.00, 4000.00),
(142, 5, 98, 80, 150.00, 12000.00),
(143, 5, 99, 80, 100.00, 8000.00),
(144, 5, 100, 80, 108.00, 8640.00),
(156, 6, 101, 150, 5.00, 750.00),
(157, 6, 102, 150, 11.00, 1650.00),
(158, 6, 103, 150, 8.00, 1200.00),
(159, 6, 104, 150, 8.00, 1200.00),
(160, 6, 105, 150, 42.00, 6300.00),
(161, 6, 106, 150, 42.00, 6300.00),
(162, 6, 107, 150, 26.00, 3900.00),
(163, 6, 108, 150, 38.00, 5700.00),
(164, 6, 109, 150, 35.00, 5250.00),
(165, 6, 110, 150, 42.00, 6300.00),
(166, 6, 111, 150, 240.00, 36000.00),
(167, 6, 112, 150, 195.00, 29250.00),
(168, 6, 113, 150, 35.00, 5250.00),
(169, 6, 114, 150, 42.00, 6300.00),
(170, 6, 115, 150, 5.00, 750.00),
(171, 6, 116, 150, 10.00, 1500.00),
(172, 6, 117, 150, 18.00, 2700.00),
(173, 6, 118, 150, 35.00, 5250.00),
(174, 6, 119, 150, 35.00, 5250.00),
(175, 6, 120, 150, 50.00, 7500.00),
(187, 7, 121, 50, 235.00, 11750.00),
(188, 7, 122, 50, 220.00, 11000.00),
(189, 7, 123, 50, 100.00, 5000.00),
(190, 7, 124, 50, 92.00, 4600.00),
(191, 7, 125, 50, 42.00, 2100.00),
(192, 7, 126, 50, 180.00, 9000.00),
(193, 7, 127, 50, 120.00, 6000.00),
(194, 7, 128, 50, 110.00, 5500.00),
(195, 7, 129, 50, 235.00, 11750.00),
(196, 7, 130, 50, 200.00, 10000.00),
(197, 7, 131, 50, 220.00, 11000.00),
(198, 7, 132, 50, 235.00, 11750.00),
(199, 7, 133, 50, 150.00, 7500.00),
(200, 7, 134, 50, 125.00, 6250.00),
(201, 7, 135, 50, 110.00, 5500.00),
(202, 7, 136, 50, 160.00, 8000.00),
(203, 7, 137, 50, 125.00, 6250.00),
(204, 7, 138, 50, 100.00, 5000.00),
(205, 7, 139, 50, 120.00, 6000.00),
(206, 7, 140, 50, 100.00, 5000.00),
(218, 8, 141, 100, 35.00, 3500.00),
(219, 8, 142, 100, 32.00, 3200.00),
(220, 8, 143, 100, 35.00, 3500.00),
(221, 8, 144, 100, 32.00, 3200.00),
(222, 8, 145, 100, 42.00, 4200.00),
(223, 8, 146, 100, 42.00, 4200.00),
(224, 8, 147, 100, 42.00, 4200.00),
(225, 8, 148, 100, 60.00, 6000.00),
(226, 8, 149, 100, 100.00, 10000.00),
(227, 8, 150, 100, 68.00, 6800.00),
(228, 8, 151, 100, 108.00, 10800.00),
(229, 8, 152, 100, 42.00, 4200.00),
(230, 8, 153, 100, 35.00, 3500.00),
(231, 8, 154, 100, 32.00, 3200.00),
(232, 8, 155, 100, 35.00, 3500.00),
(233, 8, 156, 100, 8.00, 800.00),
(234, 8, 157, 100, 35.00, 3500.00),
(235, 8, 158, 100, 75.00, 7500.00),
(236, 8, 159, 100, 60.00, 6000.00),
(237, 8, 160, 100, 100.00, 10000.00),
(312, 40, 2, 50, 58.00, 2900.00),
(313, 40, 3, 50, 58.00, 2900.00),
(314, 40, 4, 50, 60.00, 3000.00),
(315, 40, 5, 50, 62.00, 3100.00),
(319, 41, 8, 60, 18.00, 1080.00),
(320, 41, 53, 60, 24.00, 1440.00),
(321, 41, 54, 60, 26.00, 1560.00),
(322, 41, 56, 60, 50.00, 3000.00),
(323, 41, 57, 60, 115.00, 6900.00),
(324, 41, 58, 60, 115.00, 6900.00),
(325, 41, 148, 60, 60.00, 3600.00),
(326, 41, 157, 60, 35.00, 2100.00),
(334, 42, 32, 100, 5.00, 500.00),
(335, 42, 33, 100, 36.00, 3600.00),
(337, 43, 31, 100, 26.00, 2600.00),
(338, 43, 34, 100, 42.00, 4200.00),
(340, 44, 47, 80, 42.00, 3360.00),
(341, 44, 48, 80, 40.00, 3200.00),
(342, 44, 49, 80, 45.00, 3600.00),
(343, 44, 125, 80, 42.00, 3360.00),
(347, 45, 65, 60, 115.00, 6900.00),
(348, 45, 67, 60, 125.00, 7500.00),
(349, 45, 81, 60, 115.00, 6900.00),
(350, 45, 82, 60, 125.00, 7500.00),
(351, 45, 84, 60, 160.00, 9600.00),
(352, 45, 86, 60, 68.00, 4080.00),
(354, 46, 64, 80, 75.00, 6000.00),
(355, 46, 68, 80, 75.00, 6000.00),
(356, 46, 71, 80, 42.00, 3360.00),
(357, 46, 72, 80, 35.00, 2800.00),
(362, 48, 89, 100, 62.00, 6200.00),
(363, 49, 101, 200, 5.00, 1000.00),
(364, 50, 102, 100, 11.00, 1100.00),
(365, 50, 120, 100, 50.00, 5000.00),
(367, 51, 122, 50, 220.00, 11000.00),
(368, 51, 124, 50, 92.00, 4600.00),
(370, 52, 121, 50, 235.00, 11750.00),
(371, 52, 123, 50, 100.00, 5000.00),
(373, 53, 147, 80, 42.00, 3360.00),
(374, 53, 152, 80, 42.00, 3360.00),
(376, 54, 150, 60, 68.00, 4080.00),
(377, 54, 151, 60, 108.00, 6480.00),
(379, 55, 7, 50, 22.00, 1100.00),
(380, 55, 9, 50, 68.00, 3400.00),
(381, 55, 10, 50, 50.00, 2500.00),
(382, 55, 11, 50, 12.00, 600.00),
(383, 55, 12, 50, 12.00, 600.00),
(384, 55, 13, 50, 10.00, 500.00),
(385, 55, 14, 50, 78.00, 3900.00),
(386, 55, 15, 50, 20.00, 1000.00),
(387, 55, 16, 50, 22.00, 1100.00),
(388, 55, 17, 50, 35.00, 1750.00),
(389, 55, 18, 50, 32.00, 1600.00),
(390, 55, 19, 50, 8.00, 400.00),
(391, 55, 20, 50, 7.00, 350.00),
(394, 56, 26, 40, 35.00, 1400.00),
(395, 56, 27, 40, 28.00, 1120.00),
(396, 56, 28, 40, 90.00, 3600.00),
(397, 56, 29, 40, 42.00, 1680.00),
(398, 56, 36, 40, 35.00, 1400.00),
(399, 56, 37, 40, 42.00, 1680.00),
(400, 56, 38, 40, 50.00, 2000.00),
(401, 56, 39, 40, 24.00, 960.00),
(402, 56, 40, 40, 42.00, 1680.00);

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `return_number` varchar(50) NOT NULL,
  `original_sale_id` int(11) NOT NULL,
  `return_date` datetime DEFAULT current_timestamp(),
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_refund` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `user_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returns`
--

INSERT INTO `returns` (`id`, `return_number`, `original_sale_id`, `return_date`, `subtotal`, `tax`, `total_refund`, `reason`, `notes`, `status`, `user_id`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'RET-20260921-3452', 3, '2026-09-21 23:22:37', 45.00, 0.00, 45.00, 'defective', '\nRejected: eme eme si fprrd', 'rejected', 13, NULL, '2026-09-21 15:22:37', '2026-09-21 15:30:36');

-- --------------------------------------------------------

--
-- Table structure for table `return_items`
--

CREATE TABLE `return_items` (
  `id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `refund_amount` decimal(12,2) NOT NULL,
  `item_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_items`
--

INSERT INTO `return_items` (`id`, `return_id`, `product_id`, `quantity`, `refund_amount`, `item_reason`) VALUES
(1, 1, 17, 1, 0.00, 'defective');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(20) DEFAULT 'cash',
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `loyalty_points_used` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `user_id`, `customer_id`, `subtotal`, `tax`, `total_amount`, `sale_date`, `payment_method`, `amount_paid`, `change_amount`, `discount_amount`, `loyalty_points_used`) VALUES
(1, 'SM-20260921-5161', 13, 7, 465.00, 55.80, 520.80, '2026-09-21 12:42:48', 'cash', 600.00, 79.20, 0.00, 0),
(2, 'SM-20260921-4926', 13, 5, 173.00, 20.76, 193.76, '2026-09-21 12:45:53', 'cash', 193.76, 0.00, 0.00, 0),
(3, 'SM-20260921-1809', 13, 8, 45.00, 5.40, 50.40, '2026-09-21 12:47:43', 'cash', 100.00, 49.60, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 151, 1, 135.00, 135.00),
(2, 1, 133, 1, 185.00, 185.00),
(3, 1, 65, 1, 145.00, 145.00),
(4, 2, 82, 1, 155.00, 155.00),
(5, 2, 12, 1, 18.00, 18.00),
(6, 3, 17, 1, 45.00, 45.00);

-- --------------------------------------------------------

--
-- Table structure for table `session_log`
--

CREATE TABLE `session_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `session_log`
--

INSERT INTO `session_log` (`id`, `user_id`, `username`, `login_time`, `logout_time`, `ip_address`, `user_agent`, `status`) VALUES
(1, 1, 'admin', '2026-09-21 11:44:02', '2026-09-21 12:34:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(2, 13, 'Russe (cashier)', '2026-09-21 12:34:28', '2026-09-21 12:48:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(3, 1, 'admin', '2026-09-21 12:48:04', '2026-09-21 12:51:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(4, 14, 'kurt (inventory)', '2026-09-21 12:51:21', '2026-09-21 12:53:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(5, 1, 'admin', '2026-09-21 12:53:19', '2026-09-21 12:53:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(6, 14, 'kurt (inventory)', '2026-09-21 12:53:33', '2026-09-21 13:09:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(7, 1, 'admin', '2026-09-21 13:09:57', '2026-09-21 13:17:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(8, 16, 'Faith (hr)', '2026-09-21 13:17:04', '2026-09-21 13:52:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(9, 13, 'Russe (cashier)', '2026-09-21 13:52:49', '2026-09-21 13:53:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(10, 15, 'Yuki (finance)', '2026-09-21 13:53:04', '2026-09-21 14:10:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(11, 14, 'kurt (inventory)', '2026-09-21 14:10:57', '2026-09-21 14:56:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(12, 1, 'admin', '2026-09-21 14:57:00', '2026-09-21 14:57:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(13, 14, 'kurt (inventory)', '2026-09-21 14:57:54', '2026-09-21 14:58:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(14, 1, 'admin', '2026-09-21 14:58:18', '2026-09-21 14:58:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(15, 14, 'kurt (inventory)', '2026-09-21 14:58:56', '2026-09-21 14:59:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(16, 1, 'admin', '2026-09-21 14:59:39', '2026-09-21 14:59:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(17, 14, 'kurt (inventory)', '2026-09-21 14:59:56', '2026-09-21 15:08:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(18, 1, 'admin', '2026-09-21 15:08:42', '2026-09-21 15:08:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(19, 14, 'kurt (inventory)', '2026-09-21 15:08:54', '2026-09-21 15:09:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(20, 1, 'admin', '2026-09-21 15:09:20', '2026-09-21 15:09:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(21, 14, 'kurt (inventory)', '2026-09-21 15:09:42', '2026-09-21 15:18:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(22, 1, 'admin', '2026-09-21 15:18:32', '2026-09-21 15:18:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(23, 14, 'kurt (inventory)', '2026-09-21 15:18:44', '2026-09-21 15:21:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(24, 13, 'Russe (cashier)', '2026-09-21 15:21:52', '2026-09-21 15:22:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(25, 15, 'Yuki (finance)', '2026-09-21 15:22:49', '2026-09-21 15:34:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(26, 16, 'Faith (hr)', '2026-09-21 15:34:13', '2026-09-21 15:36:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out'),
(27, 15, 'Yuki (finance)', '2026-09-21 15:36:31', '2026-09-21 15:38:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'logged_out');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'store_name', 'Smart Market', '2026-07-04 18:03:44'),
(2, 'currency', '&#8369;', '2026-07-04 15:43:07'),
(3, 'tax_rate', '14', '2026-07-16 17:41:08'),
(4, 'session_timeout', '1800', '2026-07-01 16:03:55'),
(33, 'store_address', 'Burol 1, Aguinaldo hwy, Dasmarinas Cavite', '2026-07-17 17:01:42'),
(34, 'store_contact', '0912-345-6789', '2026-09-15 16:08:37'),
(35, 'vat_reg_number', '123-456-789-000', '2026-07-17 16:32:44'),
(79, 'return_hours', '20', '2026-09-06 16:10:34'),
(80, 'return_policy', 'Items can be returned within 20 hours of purchase', '2026-09-06 16:10:34');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('purchase','sale','adjustment','damage','return','correction','lost','expired') NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `movement_type`, `quantity`, `quantity_before`, `quantity_after`, `reason`, `reference_id`, `reference_type`, `user_id`, `created_at`) VALUES
(1, 41, 'damage', -2, 600, 598, 'Damage — inulam', NULL, NULL, 1, '2026-09-21 14:57:43'),
(2, 18, 'adjustment', -1, 350, 349, 'Other — pinang sopas', NULL, NULL, 1, '2026-09-21 14:58:48');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Coca-Cola Beverages Philippines, Inc.', 'Juan Dela Cruz', '0917-555-0101', 'orders@cocacola-ph.com', 'Km 18, West Service Road, Parañaque City, Metro Manila', 'Main distributor for Coca-Cola products. Delivery every Monday & Thursday.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(2, 'Pepsi-Cola Products Philippines, Inc.', 'Maria Santos', '0917-555-0102', 'sales@pepsi-ph.com', 'Manggahan Light Industrial Park, Pasig City, Metro Manila', 'Pepsi, Mountain Dew, 7-Up. Delivery every Tuesday & Friday.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(3, 'Del Monte Philippines, Inc.', 'Pedro Reyes', '0917-555-0103', 'customer@delmonte-ph.com', 'Bugo, Cagayan de Oro City, Misamis Oriental', 'Juices, tomato sauce, pineapple products. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(4, 'Universal Robina Corporation (URC)', 'Ana Bautista', '0917-555-0201', 'urc-customer@urc.com.ph', 'Bagumbayan, Quezon City, Metro Manila', 'Jack n Jill, Piattos, Nova, Chippy, Cream-O, Choco Mucho. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(5, 'Monde Nissin Corporation', 'Carlos Mendoza', '0917-555-0202', 'customercare@monde.com.ph', 'Sta. Rosa, Laguna', 'Lucky Me, Skyflakes, Fita, Eggnog. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(6, 'Rebisco Group of Companies', 'Linda Tan', '0917-555-0203', 'info@rebisco.com.ph', 'Bagumbayan, Quezon City, Metro Manila', 'Rebisco, Hansel, Rebisco Crackers. Bi-weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(7, 'Century Pacific Food, Inc.', 'Roberto Cruz', '0917-555-0301', 'info@centurypacific.com.ph', 'Taguig City, Metro Manila', 'Century Tuna, 555 Sardines, Argentina. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(8, 'Purefoods Hormel Company, Inc.', 'Gloria Aquino', '0917-555-0302', 'info@purefoods.com.ph', 'Taguig City, Metro Manila', 'Purefoods Corned Beef, Highlands, Star. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(10, 'Unilever Philippines, Inc.', 'Rosa Lim', '0917-555-0401', 'orders@unilever-ph.com', 'Bonifacio Global City, Taguig, Metro Manila', 'Safeguard, Dove, Rexona, Sunsilk, Close-Up, Pond\'s. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(11, 'Procter & Gamble Philippines, Inc.', 'Marco Villanueva', '0917-555-0402', 'info@pg-ph.com', 'Bonifacio Global City, Taguig, Metro Manila', 'Head & Shoulders, Pantene, Tide, Ariel, Downy. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(12, 'Colgate-Palmolive Philippines, Inc.', 'Michelle Garcia', '0917-555-0403', 'orders@colgate-ph.com', 'Makati City, Metro Manila', 'Colgate, Palmolive, Softsoap. Bi-weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(14, 'SC Johnson & Son Philippines, Inc.', 'Elena Cruz', '0917-555-0501', 'orders@scjohnson-ph.com', 'Pasig City, Metro Manila', 'Glade, Ziploc, Off!, Baygon. Bi-weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(15, 'Zonrox Incorporated', 'Antonio Lopez', '0917-555-0502', 'sales@zonrox-ph.com', 'Quezon City, Metro Manila', 'Zonrox Bleach products. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(16, 'Mongol Pencil Company, Inc.', 'Jose Rizal Jr.', '0917-555-0601', 'info@mongol.com.ph', 'Mandaluyong City, Metro Manila', 'Mongol Pencils, erasers. Monthly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(17, 'National Book Store Wholesale', 'Elena Villanueva', '0917-555-0602', 'wholesale@nationalbookstore.com.ph', 'Mandaluyong City, Metro Manila', 'Notebooks, pad paper, bond paper, school supplies. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(18, 'Faber-Castell Philippines, Inc.', 'Ramon Tan', '0917-555-0603', 'sales@faber-castell-ph.com', 'Pasig City, Metro Manila', 'Faber-Castell pencils, pens, art supplies. Bi-weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(19, 'San Miguel Foods, Inc.', 'Fernando Ramos', '0917-555-0701', 'orders@sanmiguel-foods.com', 'Ortigas Center, Pasig City, Metro Manila', 'Magnolia Chicken, Monterey Meats, Purefoods Frozen. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(20, 'CDO Foodsphere, Inc.', 'Teresa Aquino', '0917-555-0702', 'sales@cdo.com.ph', 'Valenzuela City, Metro Manila', 'CDO Hotdogs, Nuggets, Corned Beef. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(21, 'Purefoods Frozen Foods Division', 'Gloria Aquino', '0917-555-0302', 'info@purefoods.com.ph', 'Taguig City, Metro Manila', 'Purefoods Hotdog, Nuggets, Frozen Meats. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(22, 'Nutri-Asia, Inc. (Silver Swan, Datu Puti)', 'Roberto Sy', '0917-555-0801', 'info@nutri-asia.com.ph', 'Meycauayan, Bulacan', 'Silver Swan, Datu Puti, Mang Tomas, UFC. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(23, 'UFC Philippines (Nutri-Asia)', 'Rosa Lim', '0917-555-0802', 'orders@ufc-ph.com', 'Meycauayan, Bulacan', 'UFC Ketchup, Banana Sauce, Sweet Chili. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(25, 'Lady\'s Choice / Best Foods Philippines', 'Carlos Mendoza', '0917-555-0803', 'orders@lady-choice-ph.com', 'Pasig City, Metro Manila', 'Lady\'s Choice Mayonnaise, Best Foods. Bi-weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(26, 'Metro Grocery Wholesale Trading', 'Alfredo Lim', '0917-555-0999', 'info@metrogrocery.com.ph', 'Divisoria, Manila', 'General wholesale supplier for miscellaneous items. Weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11'),
(27, 'Pacific Bay Trading Corporation', 'Norma Cruz', '0917-555-0998', 'orders@pacificbay-ph.com', 'Binondo, Manila', 'Importer and distributor of various goods. Bi-weekly delivery.', 'active', '2026-09-21 11:51:11', '2026-09-21 11:51:11');

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_auth`
--

CREATE TABLE `two_factor_auth` (
  `user_id` int(11) NOT NULL,
  `secret` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- ⭐ MULTI-ROLE SUPPORT: `role` changed from ENUM to VARCHAR(255)
-- Roles are stored as comma-separated values (e.g., "admin,finance")
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` varchar(255) DEFAULT 'cashier',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`, `last_activity`) VALUES
(1, 'admin', '$2y$10$lQH6df3OfcpSLSxBb3Cesugfa.TolzBuutSymEGD97r1/kf2V3TOm', 'Administrator', 'admin', '2026-06-30 16:18:43', '2026-09-21 15:18:32'),
(13, 'cashier', '$2y$10$USf6IDS9a.jIdDxGF85Dy.Epz0U708CNfSYEuSRbXg17cDrUtogxy', 'Russel Infanso', 'cashier', '2026-09-21 12:34:18', '2026-09-21 15:21:52'),
(14, 'inventory', '$2y$10$ck3nksdcjEwRgAIRdHusPOtmQDPrz5IkZ83IXFhzlrrHIXQkyVpJq', 'Kurt Mendoza', 'inventory', '2026-09-21 12:51:11', '2026-09-21 15:18:44'),
(15, 'finance', '$2y$10$qWh/hI1Mn6Mlf5lKYhT3f.phjHRVJOlqChvGfQqcbWgVR/14urBJy', 'Yuki Hattori', 'finance', '2026-09-21 13:15:49', '2026-09-21 15:36:31'),
(16, 'hr', '$2y$10$i1x4OycayI/1GQlxoWJzMOmzxNvuter1YB2MRhZt2bRRws86FjP7K', 'Faith Cabrera', 'hr', '2026-09-21 13:16:56', '2026-09-21 15:34:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `archive_history`
--
ALTER TABLE `archive_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `archived_by` (`archived_by`),
  ADD KEY `restored_by` (`restored_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_employee_date` (`employee_id`,`date`),
  ADD KEY `idx_employee_date` (`employee_id`,`date`);

--
-- Indexes for table `attendance_hours`
--
ALTER TABLE `attendance_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_date` (`employee_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`expense_date`);

--
-- Indexes for table `inventory_notifications`
--
ALTER TABLE `inventory_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`notification_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll_notifications`
--
ALTER TABLE `payroll_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_id` (`payroll_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `default_supplier_id` (`default_supplier_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `idx_date` (`purchase_date`),
  ADD KEY `idx_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase` (`purchase_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_number` (`return_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_return_number` (`return_number`),
  ADD KEY `idx_sale` (`original_sale_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `return_items`
--
ALTER TABLE `return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_return` (`return_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `session_log`
--
ALTER TABLE `session_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_date` (`created_at`),
  ADD KEY `idx_type` (`movement_type`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_supplier_name` (`name`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `two_factor_auth`
--
ALTER TABLE `two_factor_auth`
  ADD PRIMARY KEY (`user_id`);

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
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `archive_history`
--
ALTER TABLE `archive_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `attendance_hours`
--
ALTER TABLE `attendance_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_notifications`
--
ALTER TABLE `inventory_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_notifications`
--
ALTER TABLE `payroll_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=409;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `return_items`
--
ALTER TABLE `return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `session_log`
--
ALTER TABLE `session_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `archive_history`
--
ALTER TABLE `archive_history`
  ADD CONSTRAINT `archive_history_ibfk_1` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `archive_history_ibfk_2` FOREIGN KEY (`restored_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_hours`
--
ALTER TABLE `attendance_hours`
  ADD CONSTRAINT `attendance_hours_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD CONSTRAINT `notification_reads_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `inventory_notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_notifications`
--
ALTER TABLE `payroll_notifications`
  ADD CONSTRAINT `payroll_notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `payroll_notifications_ibfk_2` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`default_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`original_sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `return_items`
--
ALTER TABLE `return_items`
  ADD CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `two_factor_auth`
--
ALTER TABLE `two_factor_auth`
  ADD CONSTRAINT `two_factor_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

-- --------------------------------------------------------

--
-- Table structure for table `procurement_requests`
--

CREATE TABLE IF NOT EXISTS `procurement_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `req_number` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `estimated_unit_cost` decimal(10,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `cost_center` varchar(100) DEFAULT NULL,
  `budget_code` varchar(100) DEFAULT NULL,
  `budget_amount` decimal(12,2) DEFAULT 0.00,
  `budget_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `budget_checked_by` int(11) DEFAULT NULL,
  `budget_checked_at` datetime DEFAULT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `po_total` decimal(12,2) DEFAULT NULL,
  `po_issued_at` datetime DEFAULT NULL,
  `po_acknowledged_at` datetime DEFAULT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `received_quantity` int(11) NOT NULL DEFAULT 0,
  `grn_number` varchar(50) DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `receipt_notes` text DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_amount` decimal(12,2) DEFAULT NULL,
  `invoice_quantity` int(11) DEFAULT NULL,
  `invoice_status` enum('not_submitted','held','matched','paid') NOT NULL DEFAULT 'not_submitted',
  `invoice_notes` text DEFAULT NULL,
  `invoice_received_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `payment_due_date` date DEFAULT NULL,
  `payment_scheduled_for` date DEFAULT NULL,
  `payment_scheduled_at` datetime DEFAULT NULL,
  `payment_method` enum('bank_transfer','cheque','virtual_card') DEFAULT NULL,
  `payment_advice_sent_at` datetime DEFAULT NULL,
  `payment_advice_recipient` varchar(255) DEFAULT NULL,
  `supplier_reconciled_at` datetime DEFAULT NULL,
  `accrual_released_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `req_number` (`req_number`),
  KEY `product_id` (`product_id`),
  KEY `supplier_id` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `procurement_supplier_performance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `on_time_in_full` enum('yes','no','partial') NOT NULL,
  `quality_rating` tinyint(4) NOT NULL,
  `responsiveness_rating` tinyint(4) NOT NULL,
  `issue_resolution` text DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  `rated_by` int(11) DEFAULT NULL,
  `rated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id` (`request_id`),
  KEY `idx_procurement_performance_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `procurement_quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `lead_time_days` int(11) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `technical_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_procurement_quote_request` (`request_id`),
  KEY `idx_procurement_quote_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `payment_tokens`
--

CREATE TABLE IF NOT EXISTS `payment_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `card_brand` varchar(20) DEFAULT 'unknown',
  `last4` char(4) NOT NULL,
  `expiry_month` tinyint(2) DEFAULT NULL,
  `expiry_year` smallint(4) DEFAULT NULL,
  `cardholder_name` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Card-token link for sales (real card numbers are never stored)
ALTER TABLE `sales` ADD COLUMN IF NOT EXISTS `payment_token_id` int(11) DEFAULT NULL AFTER `payment_method`;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
