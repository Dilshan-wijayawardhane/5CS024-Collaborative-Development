-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 06:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `collaborative_dev`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `AchievementID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Category` enum('Login','Facility','Event','Game','Social','Special') NOT NULL,
  `Icon` varchar(50) DEFAULT 'fa-trophy',
  `PointsReward` int(11) DEFAULT 0,
  `Requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON defining achievement requirements' CHECK (json_valid(`Requirements`)),
  `IsHidden` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `achievements`
--

INSERT INTO `achievements` (`AchievementID`, `Name`, `Description`, `Category`, `Icon`, `PointsReward`, `Requirements`, `IsHidden`, `CreatedAt`) VALUES
(1, 'Early Bird', 'Login 7 days in a row', 'Login', 'fa-sun', 100, '{\"streak_days\": 7}', 0, '2026-03-13 03:05:32'),
(2, 'Social Butterfly', 'RSVP to 10 events', 'Event', 'fa-calendar-check', 150, '{\"events_rsvped\": 10}', 0, '2026-03-13 03:05:32'),
(3, 'Gym Rat', 'Check in to gym 20 times', 'Facility', 'fa-dumbbell', 200, '{\"gym_checkins\": 20}', 0, '2026-03-13 03:05:32'),
(4, 'Bookworm', 'Borrow 15 books', 'Facility', 'fa-book', 150, '{\"books_borrowed\": 15}', 0, '2026-03-13 03:05:32'),
(5, 'Game Master', 'Win 5 games', 'Game', 'fa-gamepad', 250, '{\"games_won\": 5}', 0, '2026-03-13 03:05:32'),
(6, 'Campus Explorer', 'Visit all facilities', 'Facility', 'fa-map-marked-alt', 300, '{\"facilities_visited\": \"all\"}', 0, '2026-03-13 03:05:32'),
(7, 'Club Hopper', 'Join 3 different clubs', 'Social', 'fa-users', 100, '{\"clubs_joined\": 3}', 0, '2026-03-13 03:05:32'),
(8, 'Transport Guru', 'Use transport feature 50 times', 'Special', 'fa-bus', 150, '{\"transport_uses\": 50}', 0, '2026-03-13 03:05:32'),
(9, 'Challenge Champion', 'Complete 10 daily challenges', 'Game', 'fa-tasks', 200, '{\"challenges_completed\": 10}', 0, '2026-03-13 03:05:32');

-- --------------------------------------------------------

--
-- Table structure for table `activemembers`
--

CREATE TABLE `activemembers` (
  `UserID` int(11) DEFAULT NULL,
  `StudentID` varchar(20) DEFAULT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `PointsBalance` int(11) DEFAULT NULL,
  `MembershipStatus` enum('Active','Inactive','Expired') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activitylogs`
--

CREATE TABLE `activitylogs` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Action` varchar(50) NOT NULL,
  `Details` text DEFAULT NULL,
  `Timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activitylogs`
--

INSERT INTO `activitylogs` (`LogID`, `UserID`, `Action`, `Details`, `Timestamp`) VALUES
(1, 7, 'ADD_OFFER', 'Added offer: Cake', '2026-04-29 09:39:51'),
(2, 7, 'ADD_OFFER', 'Added offer: Banana Cake', '2026-04-29 09:41:35'),
(3, 7, 'UPDATE_CROWD', 'Facility ID: 1, Crowd: 270/487', '2026-04-29 09:44:01');

-- --------------------------------------------------------

--
-- Table structure for table `adminlogs`
--

CREATE TABLE `adminlogs` (
  `LogID` int(11) NOT NULL,
  `AdminID` int(11) NOT NULL,
  `Action` varchar(100) NOT NULL,
  `Details` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `LoggedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditlogs`
--

CREATE TABLE `auditlogs` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Action` varchar(255) NOT NULL,
  `TableName` varchar(50) DEFAULT NULL,
  `RecordID` int(11) DEFAULT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `IPAddress` varchar(45) DEFAULT NULL,
  `UserAgent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auditlogs`
--

INSERT INTO `auditlogs` (`LogID`, `UserID`, `Action`, `TableName`, `RecordID`, `Timestamp`, `IPAddress`, `UserAgent`) VALUES
(1, 5, 'REGISTER', NULL, NULL, '2026-03-24 06:01:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(2, 5, 'LOGIN', NULL, NULL, '2026-03-24 06:01:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(3, 5, 'CHECKIN', 'Facilities', 2, '2026-03-24 06:01:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(4, 5, 'CHECKIN', 'Facilities', 1, '2026-03-24 06:02:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(5, 6, 'REGISTER', NULL, NULL, '2026-03-24 09:12:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(6, 6, 'LOGIN', NULL, NULL, '2026-03-24 09:12:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(7, 6, 'CHECKIN', 'Facilities', 1, '2026-03-24 09:12:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(8, 6, 'BORROW_BOOK', 'books', 9, '2026-03-24 09:12:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(9, 5, 'LOGIN', NULL, NULL, '2026-03-27 17:12:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(10, 5, 'CHECKIN', 'Facilities', 2, '2026-03-27 17:12:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(11, 5, 'JOIN_CLASS', 'fitness_classes', 2, '2026-03-27 17:12:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(12, 5, 'CHECKIN', 'Facilities', 1, '2026-03-27 17:12:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(13, 5, 'BORROW_BOOK', 'books', 9, '2026-03-27 17:12:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(14, 5, 'CHECKIN', 'Facilities', 3, '2026-03-27 17:13:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(15, 5, 'CHECKIN', 'Facilities', 4, '2026-03-27 17:13:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(16, 5, 'LOGOUT', NULL, NULL, '2026-03-27 17:48:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(17, 5, 'LOGIN', NULL, NULL, '2026-03-27 17:54:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(18, 5, 'LOGIN', NULL, NULL, '2026-03-28 02:28:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(19, 5, 'JOIN_CLUB', 'Clubs', 1, '2026-03-28 02:52:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(20, 5, 'LOGOUT', NULL, NULL, '2026-03-28 04:30:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(21, 5, 'LOGIN', NULL, NULL, '2026-03-28 04:48:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(22, 5, 'LOGIN', NULL, NULL, '2026-04-06 05:50:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(23, 5, 'LOGIN', NULL, NULL, '2026-04-06 06:24:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(24, 5, 'LOGIN', NULL, NULL, '2026-04-21 02:59:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(25, 5, 'CHECKIN', 'Facilities', 1, '2026-04-21 05:26:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(26, 5, 'CHECKIN', 'Facilities', 2, '2026-04-21 05:28:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(27, 5, 'LOGIN', NULL, NULL, '2026-04-21 07:34:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(28, 5, 'LOGIN', NULL, NULL, '2026-04-21 15:10:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(29, 5, 'LOGOUT', NULL, NULL, '2026-04-21 15:26:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(30, 5, 'LOGIN', NULL, NULL, '2026-04-21 15:26:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(32, 5, 'LOGIN', NULL, NULL, '2026-04-21 16:23:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(33, 5, 'RETURN_BOOK', 'books', 9, '2026-04-21 16:26:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(34, 5, 'LOGIN', NULL, NULL, '2026-04-21 16:41:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(35, 5, 'LOGIN', NULL, NULL, '2026-04-22 06:14:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(36, 5, 'LOGIN', NULL, NULL, '2026-04-22 07:35:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(37, 5, 'LOGIN', NULL, NULL, '2026-04-23 06:29:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(38, 5, 'LOGOUT', NULL, NULL, '2026-04-23 07:09:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(39, 5, 'LOGIN', NULL, NULL, '2026-04-23 07:26:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(40, 5, 'LOGOUT', NULL, NULL, '2026-04-23 07:28:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(41, 5, 'LOGIN', NULL, NULL, '2026-04-23 07:49:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(42, 5, 'LOGOUT', NULL, NULL, '2026-04-23 07:49:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(43, 5, 'LOGIN', NULL, NULL, '2026-04-23 07:59:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(44, 5, 'LOGOUT', NULL, NULL, '2026-04-23 08:02:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(45, 5, 'LOGIN', NULL, NULL, '2026-04-23 08:26:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(46, 5, 'LOGIN', NULL, NULL, '2026-04-23 16:39:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(47, 5, 'LOGOUT', NULL, NULL, '2026-04-23 18:18:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(48, 5, 'LOGIN', NULL, NULL, '2026-04-23 18:19:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(49, 5, 'LEAVE_CLUB', 'Clubs', 1, '2026-04-23 18:32:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(50, 5, 'JOIN_CLUB', 'Clubs', 4, '2026-04-23 18:45:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(51, 5, 'JOIN_CLUB', 'Clubs', 2, '2026-04-23 18:46:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(52, 5, 'JOIN_CLUB', 'Clubs', 3, '2026-04-23 18:46:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(53, 5, 'LEAVE_CLUB', 'Clubs', 4, '2026-04-23 18:47:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(54, 5, 'LEAVE_CLUB', 'Clubs', 3, '2026-04-23 18:47:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(55, 5, 'CHECKIN', 'Facilities', 2, '2026-04-23 18:56:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(56, 5, 'LOGIN', NULL, NULL, '2026-04-24 06:11:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(57, 5, 'JOIN_CLASS', 'fitness_classes', 3, '2026-04-24 07:37:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(58, 5, 'CHECKIN', 'Facilities', 1, '2026-04-24 07:38:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(59, 5, 'BORROW_BOOK', 'books', 9, '2026-04-24 08:00:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(60, 5, 'CHECKIN', 'Facilities', 3, '2026-04-24 08:56:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(61, 5, 'LOGIN', NULL, NULL, '2026-04-24 14:10:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(62, 5, 'ORDER_PLACED', 'Orders', 4, '2026-04-24 15:18:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(63, 5, 'ORDER_PLACED', 'Orders', 6, '2026-04-24 15:18:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(64, 5, 'ORDER_PLACED', 'Orders', 8, '2026-04-24 15:20:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(65, 5, 'ORDER_PLACED', 'Orders', 12, '2026-04-24 15:21:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(66, 5, 'CHECKIN', 'Facilities', 4, '2026-04-24 15:32:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(67, 5, 'ORDER_PLACED', 'Orders', 14, '2026-04-24 15:40:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(68, 5, 'ORDER_PLACED', 'Orders', 16, '2026-04-24 15:40:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(69, 5, 'ORDER_PLACED', 'Orders', 18, '2026-04-24 15:40:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(70, 5, 'ORDER_PLACED', 'Orders', 20, '2026-04-24 15:42:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(71, 5, 'ORDER_PLACED', 'Orders', 22, '2026-04-24 15:46:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(72, 5, 'ORDER_PLACED', 'Orders', 23, '2026-04-24 15:52:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(73, 5, 'ORDER_PLACED', 'Orders', 24, '2026-04-24 15:52:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(74, 5, 'CHECKIN', 'Facilities', 5, '2026-04-24 16:12:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(75, 5, 'BOOK_FIELD', 'field_bookings', 0, '2026-04-24 17:42:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(76, 5, 'CANCEL_BOOKING', 'field_bookings', 1, '2026-04-24 17:42:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(77, 5, 'CHECKIN', 'Facilities', 6, '2026-04-24 17:53:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(78, 5, 'LOGOUT', NULL, NULL, '2026-04-24 17:57:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(79, 5, 'LOGIN', NULL, NULL, '2026-04-24 17:57:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(80, 5, 'CHECKIN', 'Facilities', 2, '2026-04-24 18:37:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(81, 5, 'CHECKIN', 'Facilities', 1, '2026-04-24 18:38:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(82, 5, 'CHECKIN', 'Facilities', 3, '2026-04-24 19:07:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(83, 5, 'CHECKIN', 'Facilities', 4, '2026-04-24 19:07:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(84, 5, 'CHECKIN', 'Facilities', 5, '2026-04-24 19:08:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(85, 5, 'LOGIN', NULL, NULL, '2026-04-26 07:07:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(86, 5, 'CHECKIN', 'Facilities', 1, '2026-04-26 07:08:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(87, 5, 'CHECKIN', 'Facilities', 3, '2026-04-26 07:09:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(88, 5, 'CHECKIN', 'Facilities', 4, '2026-04-26 07:09:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(89, 5, 'BUY_PASS', 'TransportPasses', 0, '2026-04-26 07:09:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(90, 5, 'CHECKIN', 'Facilities', 6, '2026-04-26 07:17:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(91, 5, 'CHECKIN', 'Facilities', 5, '2026-04-26 07:18:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(92, 5, 'CHECKIN', 'Facilities', 2, '2026-04-26 08:42:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(93, 5, 'LOGIN', NULL, NULL, '2026-04-26 09:12:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(94, 5, 'LEAVE_CLUB', 'Clubs', 2, '2026-04-26 09:13:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(95, 5, 'LOGOUT', NULL, NULL, '2026-04-26 11:43:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(96, 5, 'LOGIN', NULL, NULL, '2026-04-26 11:45:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(97, 5, 'LOGOUT', NULL, NULL, '2026-04-26 13:18:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(98, 6, 'LOGIN', NULL, NULL, '2026-04-26 13:28:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(99, 6, 'CHECKIN', 'Facilities', 2, '2026-04-26 13:29:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(100, 6, 'JOIN_CLASS', 'fitness_classes', 2, '2026-04-26 13:29:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(101, 6, 'JOIN_CLASS', 'fitness_classes', 3, '2026-04-26 13:29:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(102, 6, 'CHECKIN', 'Facilities', 1, '2026-04-26 13:29:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(103, 6, 'BORROW_BOOK', 'books', 6, '2026-04-26 13:29:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(104, 6, 'CHECKIN', 'Facilities', 3, '2026-04-26 13:30:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(105, 6, 'CHECKIN', 'Facilities', 6, '2026-04-26 13:30:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(106, 6, 'JOIN_CLUB', 'Clubs', 1, '2026-04-26 13:31:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(107, 6, 'JOIN_CLUB', 'Clubs', 2, '2026-04-26 13:31:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(108, 6, 'LEAVE_CLUB', 'Clubs', 1, '2026-04-26 13:31:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(109, 6, 'CHECKIN', 'Facilities', 4, '2026-04-26 13:32:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(110, 6, 'LEAVE_CLUB', 'Clubs', 2, '2026-04-26 15:58:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(111, 6, 'JOIN_CLUB', 'Clubs', 3, '2026-04-26 16:00:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(112, 6, 'LEAVE_CLUB', 'Clubs', 3, '2026-04-26 16:01:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(113, 6, 'JOIN_CLUB', 'Clubs', 4, '2026-04-26 16:08:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(114, 6, 'LEAVE_CLUB', 'Clubs', 4, '2026-04-26 16:08:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(115, 6, 'LEAVE_CLUB', 'Clubs', 1, '2026-04-26 16:20:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(116, 6, 'LEAVE_CLUB', 'Clubs', 2, '2026-04-26 16:22:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(117, 6, 'LEAVE_CLUB', 'Clubs', 3, '2026-04-26 16:22:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(118, 6, 'LEAVE_CLUB', 'Clubs', 1, '2026-04-26 16:23:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(119, 6, 'BUY_PASS', 'TransportPasses', 0, '2026-04-26 16:57:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(120, 6, 'LOGOUT', NULL, NULL, '2026-04-26 17:38:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(121, 6, 'LOGIN', NULL, NULL, '2026-04-26 17:38:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(122, 6, 'LOGOUT', NULL, NULL, '2026-04-26 18:30:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(123, 5, 'LOGIN', NULL, NULL, '2026-04-26 18:34:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(124, 5, 'CHECKIN', 'Facilities', 2, '2026-04-26 18:35:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(125, 5, 'CHECKIN', 'Facilities', 4, '2026-04-26 18:35:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(126, 7, 'LOGIN', NULL, NULL, '2026-04-26 18:39:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(127, 5, 'LOGIN', NULL, NULL, '2026-04-27 04:50:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(128, 6, 'LOGOUT', NULL, NULL, '2026-04-27 06:16:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(129, 7, 'LOGIN', NULL, NULL, '2026-04-27 06:16:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(130, 7, 'CHECKIN', 'Facilities', 1, '2026-04-27 06:19:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(131, 7, 'CHECKIN', 'Facilities', 4, '2026-04-27 06:19:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(132, 7, 'ADMIN_LOGOUT', NULL, NULL, '2026-04-27 06:23:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(133, 5, 'LOGIN', NULL, NULL, '2026-04-27 06:24:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(134, 5, 'LOGOUT', NULL, NULL, '2026-04-27 06:59:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(135, 5, 'LOGIN', NULL, NULL, '2026-04-27 06:59:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(136, 5, 'CHECKIN', 'Facilities', 3, '2026-04-27 07:03:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(137, 5, 'LOGOUT', NULL, NULL, '2026-04-27 07:17:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(138, 6, 'LOGIN', NULL, NULL, '2026-04-27 07:17:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(139, 6, 'CHECKIN', 'Facilities', 5, '2026-04-27 07:25:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(140, 6, 'CHECKIN', 'Facilities', 4, '2026-04-27 07:39:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(141, 6, 'LOGOUT', NULL, NULL, '2026-04-27 07:57:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(142, 8, 'REGISTER', NULL, NULL, '2026-04-27 07:59:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(143, 8, 'LOGIN', NULL, NULL, '2026-04-27 07:59:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(144, 8, 'CHECKIN', 'Facilities', 2, '2026-04-27 08:00:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(145, 8, 'JOIN_CLASS', 'fitness_classes', 2, '2026-04-27 08:00:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(146, 8, 'CHECKIN', 'Facilities', 1, '2026-04-27 08:00:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(147, 8, 'BORROW_BOOK', 'books', 9, '2026-04-27 08:00:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(148, 8, 'LOGOUT', NULL, NULL, '2026-04-27 08:02:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(149, 7, 'LOGIN', NULL, NULL, '2026-04-27 08:02:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(150, 7, 'ADMIN_LOGOUT', NULL, NULL, '2026-04-27 08:06:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(151, 5, 'LOGIN', NULL, NULL, '2026-04-27 08:06:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(152, 5, 'CHECKIN', 'Facilities', 1, '2026-04-27 08:19:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(153, 5, 'BORROW_BOOK', 'books', 10, '2026-04-27 08:19:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(154, 5, 'CHECKIN', 'Facilities', 5, '2026-04-27 08:20:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(155, 5, 'BOOK_FIELD', 'field_bookings', 0, '2026-04-27 08:20:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(156, 5, 'CHECKIN', 'Facilities', 6, '2026-04-27 08:20:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(157, 5, 'PLAY_GAME', 'GameField', 0, '2026-04-27 08:22:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(158, 5, 'BUY_PASS', 'TransportPasses', 0, '2026-04-27 08:23:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(159, 5, 'JOIN_CLASS', 'fitness_classes', 4, '2026-04-27 08:29:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(160, 5, 'JOIN_CLASS', 'fitness_classes', 1, '2026-04-27 18:03:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(161, 5, 'JOIN_REQUEST', 'Clubs', 1, '2026-04-27 18:04:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(162, 5, 'JOIN_REQUEST', 'Clubs', 2, '2026-04-27 18:04:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(163, 5, 'LOGOUT', NULL, NULL, '2026-04-27 18:07:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(164, 7, 'LOGIN', NULL, NULL, '2026-04-27 18:07:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(165, 7, 'ADMIN_LOGOUT', NULL, NULL, '2026-04-27 18:19:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(166, 5, 'LOGIN', NULL, NULL, '2026-04-27 18:20:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(167, 5, 'LOGIN', NULL, NULL, '2026-04-28 10:57:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(168, 5, 'CHECKIN', 'Facilities', 2, '2026-04-28 10:58:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(169, 5, 'CHECKIN', 'Facilities', 1, '2026-04-28 10:58:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(170, 5, 'CHECKIN', 'Facilities', 3, '2026-04-28 10:58:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(171, 5, 'CHECKIN', 'Facilities', 4, '2026-04-28 10:59:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(172, 5, 'LOGOUT', NULL, NULL, '2026-04-28 12:00:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(173, 6, 'LOGIN', NULL, NULL, '2026-04-28 12:00:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(174, 6, 'LEAVE_CLUB', 'Clubs', 2, '2026-04-28 12:01:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(175, 6, 'LOGOUT', NULL, NULL, '2026-04-28 12:03:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(176, NULL, 'REGISTER', NULL, NULL, '2026-04-28 12:05:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(177, NULL, 'LOGIN', NULL, NULL, '2026-04-28 12:05:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(178, NULL, 'CHECKIN', 'Facilities', 2, '2026-04-28 12:06:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(180, 10, 'REGISTER', NULL, NULL, '2026-04-28 12:20:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(181, 10, 'LOGIN', NULL, NULL, '2026-04-28 12:20:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(182, 10, 'LOGOUT', NULL, NULL, '2026-04-28 12:27:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(183, 5, 'LOGIN', NULL, NULL, '2026-04-28 12:27:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(184, 5, 'Earned 20 points from QR scan at Gym', 'QRScan', 0, '2026-04-28 17:20:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(185, 5, 'Earned 5 points from QR scan at Library', 'QRScan', 0, '2026-04-28 17:20:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(186, 5, 'Earned 20 points from QR scan at Café', 'QRScan', 0, '2026-04-28 17:20:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(187, 5, 'Earned 50 points from QR scan at Event', 'QRScan', 0, '2026-04-28 17:20:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(188, 5, 'Earned 15 points from QR scan at Game', 'QRScan', 0, '2026-04-28 17:20:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(189, 5, 'LOGIN', NULL, NULL, '2026-04-29 03:28:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(190, 5, 'CHECKIN', 'Facilities', 2, '2026-04-29 03:32:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(191, 5, 'CHECKIN', 'Facilities', 6, '2026-04-29 03:32:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(192, 5, 'LOGOUT', NULL, NULL, '2026-04-29 04:08:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(193, 7, 'LOGIN', NULL, NULL, '2026-04-29 04:08:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(194, 7, 'CHECKIN', 'Facilities', 3, '2026-04-29 04:10:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(195, 7, 'CHECKIN', 'Facilities', 4, '2026-04-29 04:14:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(196, 7, 'JOIN_REQUEST', 'Clubs', 1, '2026-04-29 04:15:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(197, 5, 'LOGIN', NULL, NULL, '2026-04-29 05:46:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(198, 5, 'LOGIN', NULL, NULL, '2026-04-29 07:47:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(199, 5, 'LOGOUT', NULL, NULL, '2026-04-29 09:55:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(200, 7, 'LOGIN', NULL, NULL, '2026-04-29 09:55:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(201, 7, 'LOGOUT', NULL, NULL, '2026-04-29 09:58:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(202, 5, 'LOGIN', NULL, NULL, '2026-04-29 09:58:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `available` int(11) DEFAULT 1,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `isbn`, `category`, `quantity`, `available`, `description`, `image`, `created_at`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', NULL, 'Fiction', 3, 3, 'A classic novel about the American Dream', 'the-great-gatsby.jpg', '2026-03-23 08:23:45'),
(2, 'To Kill a Mockingbird', 'Harper Lee', NULL, 'Fiction', 2, 2, 'A story of racial injustice in the Deep South', 'to-kill-a-mockingbird.jpg', '2026-03-23 08:23:45'),
(3, '1984', 'George Orwell', NULL, 'Science Fiction', 4, 4, 'Dystopian social science fiction novel', '1984.jpg', '2026-03-23 08:23:45'),
(4, 'Pride and Prejudice', 'Jane Austen', NULL, 'Romance', 2, 2, 'A romantic novel of manners', 'pride-and-prejudice.jpg', '2026-03-23 08:23:45'),
(5, 'The Catcher in the Rye', 'J.D. Salinger', NULL, 'Fiction', 2, 2, 'Story of teenage alienation', 'catcher-in-the-rye.jpg', '2026-03-23 08:23:45'),
(6, 'Introduction to Algorithms', 'Thomas H. Cormen', NULL, 'Academic', 5, 4, 'Comprehensive algorithms textbook', 'introduction-to-algorithms.jpg', '2026-03-23 08:23:45'),
(7, 'Clean Code', 'Robert C. Martin', NULL, 'Programming', 3, 3, 'Handbook of agile software craftsmanship', 'clean-code.jpg', '2026-03-23 08:23:45'),
(8, 'The Pragmatic Programmer', 'David Thomas', NULL, 'Programming', 2, 2, 'Your journey to mastery', 'pragmatic-programmer.jpg', '2026-03-23 08:23:45'),
(9, 'Database Systems', 'Elmasri & Navathe', NULL, 'Academic', 3, 0, 'Complete database textbook', 'database-systems.jpg', '2026-03-23 08:23:45'),
(10, 'Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', NULL, 'Fantasy', 4, 3, 'First book in the Harry Potter series', 'harry-potter.jpg', '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `borrowed_books`
--

CREATE TABLE `borrowed_books` (
  `borrow_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date DEFAULT curdate(),
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `fine` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrowed_books`
--

INSERT INTO `borrowed_books` (`borrow_id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `fine`) VALUES
(1, 6, 9, '2026-03-24', '2026-04-07', NULL, 'borrowed', 0.00),
(2, 5, 9, '2026-03-27', '2026-04-10', '2026-04-21', 'returned', 0.00),
(3, 5, 9, '2026-04-24', '2026-05-08', NULL, 'borrowed', 0.00),
(4, 6, 6, '2026-04-26', '2026-05-10', NULL, 'borrowed', 0.00),
(5, 8, 9, '2026-04-27', '2026-05-11', NULL, 'borrowed', 0.00),
(6, 5, 10, '2026-04-27', '2026-05-11', NULL, 'borrowed', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `bus_routes`
--

CREATE TABLE `bus_routes` (
  `id` int(11) NOT NULL,
  `route_id` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `updated_time` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bus_routes`
--

INSERT INTO `bus_routes` (`id`, `route_id`, `location`, `updated_time`, `created_at`) VALUES
(1, 'cinec', 'colombo', '11:19 PM', '2026-03-23 08:23:45'),
(2, 'gampaha1', 'Gampaha', '10:15 AM', '2026-03-23 08:23:45'),
(3, 'gampaha2', 'Kadawatha', '10:45 AM', '2026-03-23 08:23:45'),
(4, 'hendala', 'Hendala', '10:20 AM', '2026-03-23 08:23:45'),
(5, 'moratuwa', 'Moratuwa', '10:10 AM', '2026-03-23 08:23:45'),
(6, 'negombo', 'Negombo', '10:00 AM', '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `cafe_menu`
--

CREATE TABLE `cafe_menu` (
  `item_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('Food','Beverage','Dessert') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `points_price` int(11) DEFAULT 0,
  `image_icon` varchar(50) DEFAULT 'fa-utensils',
  `available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cafe_menu`
--

INSERT INTO `cafe_menu` (`item_id`, `name`, `category`, `price`, `points_price`, `image_icon`, `available`, `created_at`) VALUES
(1, 'Chicken Sandwich', 'Food', 450.00, 45, 'fa-burger', 1, '2026-03-23 08:23:45'),
(2, 'Veggie Wrap', 'Food', 350.00, 35, 'fa-leaf', 1, '2026-03-23 08:23:45'),
(3, 'Chicken Rice', 'Food', 550.00, 55, 'fa-bowl-food', 1, '2026-03-23 08:23:45'),
(4, 'Pasta', 'Food', 480.00, 48, 'fa-utensils', 1, '2026-03-23 08:23:45'),
(5, 'Coffee', 'Beverage', 250.00, 25, 'fa-mug-hot', 1, '2026-03-23 08:23:45'),
(6, 'Tea', 'Beverage', 150.00, 15, 'fa-mug-saucer', 1, '2026-03-23 08:23:45'),
(7, 'Soft Drink', 'Beverage', 200.00, 20, 'fa-bottle-water', 1, '2026-03-23 08:23:45'),
(8, 'Fresh Juice', 'Beverage', 300.00, 30, 'fa-glass-water', 1, '2026-03-23 08:23:45'),
(9, 'Chocolate Cake', 'Dessert', 350.00, 35, 'fa-cake-candles', 1, '2026-03-23 08:23:45'),
(10, 'Ice Cream', 'Dessert', 250.00, 25, 'fa-ice-cream', 1, '2026-03-23 08:23:45'),
(11, 'Fruit Salad', 'Dessert', 280.00, 28, 'fa-bowl-food', 1, '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `campus_locations`
--

CREATE TABLE `campus_locations` (
  `location_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('library','gym','cafeteria','lecture_hall','student_union','sports_complex','parking','other') NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` varchar(500) DEFAULT NULL,
  `has_360_view` tinyint(1) DEFAULT 0,
  `has_accessibility` tinyint(1) DEFAULT 0,
  `has_elevator` tinyint(1) DEFAULT 0,
  `has_ramp` tinyint(1) DEFAULT 0,
  `opening_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opening_hours`)),
  `contact_info` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-building',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campus_locations`
--

INSERT INTO `campus_locations` (`location_id`, `name`, `description`, `category`, `latitude`, `longitude`, `address`, `has_360_view`, `has_accessibility`, `has_elevator`, `has_ramp`, `opening_hours`, `contact_info`, `image_url`, `icon`, `created_at`) VALUES
(28, 'Main Academic Building', 'The main academic block with lecture halls, faculty offices, and administrative departments.', 'lecture_hall', 6.90575800, 79.96825300, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-building', '2026-03-16 11:16:12'),
(29, 'Library & Resource Center', 'Central library with extensive collection of maritime, engineering, and business resources.', 'library', 6.90580000, 79.96830000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-book', '2026-03-16 11:16:12'),
(30, 'Maritime Simulation Center', 'State-of-the-art maritime navigation simulators and bridge simulation labs.', 'lecture_hall', 6.90570000, 79.96820000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-ship', '2026-03-16 11:16:12'),
(31, 'Engineering Complex', 'Engineering labs, workshops, and lecture halls for engineering programs.', 'lecture_hall', 6.90585000, 79.96835000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-gears', '2026-03-16 11:16:12'),
(32, 'Student Union Building', 'Hub for student activities, clubs, cafeteria, and social spaces.', 'student_union', 6.90568000, 79.96815000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-users', '2026-03-16 11:16:12'),
(33, 'Campus Cafeteria', 'Main dining area serving meals, snacks, and beverages.', 'cafeteria', 6.90572000, 79.96818000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-utensils', '2026-03-16 11:16:12'),
(34, 'Sports Complex', 'Indoor sports facilities, gymnasium, and fitness center.', 'sports_complex', 6.90590000, 79.96840000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-dumbbell', '2026-03-16 11:16:12'),
(35, 'IT Center', 'Computer labs, IT services, and programming labs.', 'lecture_hall', 6.90578000, 79.96822000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-laptop', '2026-03-16 11:16:12'),
(36, 'Parking Area', 'Student and staff parking facilities.', 'parking', 6.90565000, 79.96810000, 'CINEC Campus, Malabe', 0, 1, 0, 0, NULL, NULL, NULL, 'fa-parking', '2026-03-16 11:16:12'),
(37, 'Auditorium', 'Main auditorium for events, seminars, and ceremonies.', 'lecture_hall', 6.90582000, 79.96828000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-video', '2026-03-16 11:16:12'),
(38, 'Hostel Block A', 'Student accommodation facilities.', 'other', 6.90560000, 79.96805000, 'CINEC Campus, Malabe', 0, 1, 0, 0, NULL, NULL, NULL, 'fa-bed', '2026-03-16 11:16:12'),
(39, 'Hostel Block B', 'Student accommodation facilities.', 'other', 6.90562000, 79.96807000, 'CINEC Campus, Malabe', 0, 1, 0, 0, NULL, NULL, NULL, 'fa-bed', '2026-03-16 11:16:12'),
(40, 'Marine Engineering Lab', 'Specialized labs for marine engineering practicals.', 'lecture_hall', 6.90574000, 79.96819000, 'CINEC Campus, Malabe', 1, 0, 0, 0, NULL, NULL, NULL, 'fa-oil-well', '2026-03-16 11:16:12'),
(41, 'Navigation Lab', 'Navigation equipment and chart room.', 'lecture_hall', 6.90576000, 79.96821000, 'CINEC Campus, Malabe', 1, 0, 0, 0, NULL, NULL, NULL, 'fa-compass', '2026-03-16 11:16:12'),
(42, 'Campus Medical Center', 'First aid and basic medical services for students.', 'other', 6.90583000, 79.96829000, 'CINEC Campus, Malabe', 0, 1, 0, 0, NULL, NULL, NULL, 'fa-hospital', '2026-03-16 11:16:12'),
(43, 'Career Guidance Unit', 'Career counseling and placement services.', 'other', 6.90584000, 79.96831000, 'CINEC Campus, Malabe', 0, 1, 0, 0, NULL, NULL, NULL, 'fa-briefcase', '2026-03-16 11:16:12'),
(44, 'Examination Hall', 'Central examination center.', 'lecture_hall', 6.90581000, 79.96826000, 'CINEC Campus, Malabe', 0, 1, 0, 0, NULL, NULL, NULL, 'fa-pen', '2026-03-16 11:16:12'),
(45, 'Campus Security', 'Security office and lost & found.', 'other', 6.90567000, 79.96812000, 'CINEC Campus, Malabe', 0, 1, 0, 0, NULL, NULL, NULL, 'fa-shield', '2026-03-16 11:16:12'),
(46, 'Wi-Fi Hotspot Area', 'Open area with high-speed Wi-Fi access.', 'other', 6.90588000, 79.96838000, 'CINEC Campus, Malabe', 0, 1, 0, 0, NULL, NULL, NULL, 'fa-wifi', '2026-03-16 11:16:12'),
(47, 'Study Plaza', 'Outdoor study area with seating.', 'other', 6.90586000, 79.96836000, 'CINEC Campus, Malabe', 1, 1, 0, 0, NULL, NULL, NULL, 'fa-tree', '2026-03-16 11:16:12');

-- --------------------------------------------------------

--
-- Table structure for table `campus_transport`
--

CREATE TABLE `campus_transport` (
  `route_id` int(11) NOT NULL,
  `from_campus` varchar(100) NOT NULL,
  `to_campus` varchar(100) NOT NULL,
  `next_departure` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'On Time',
  `frequency` varchar(100) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campus_transport`
--

INSERT INTO `campus_transport` (`route_id`, `from_campus`, `to_campus`, `next_departure`, `status`, `frequency`, `last_updated`) VALUES
(1, 'Negombo', 'CINEC', '6:15 AM', 'On Time', 'Morning Pickup', '2026-03-23 08:23:45'),
(2, 'Gampaha', 'CINEC', '6:20 AM', 'On Time', 'Morning Pickup', '2026-03-23 08:23:45'),
(3, 'Gampaha (via Oruthota)', 'CINEC', '6:25 AM', 'On Time', 'Morning Pickup', '2026-03-23 08:23:45'),
(4, 'Hendala', 'CINEC', '6:30 AM', 'On Time', 'Morning Pickup', '2026-03-23 08:23:45'),
(5, 'Moratuwa', 'CINEC', '6:20 AM', 'On Time', 'Morning Pickup', '2026-03-23 08:23:45'),
(6, 'CINEC', 'All Locations', '5:05 PM', 'On Time', 'Evening Departure', '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `checkins`
--

CREATE TABLE `checkins` (
  `CheckInID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FacilityID` int(11) NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `PointsAwarded` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `checkins`
--

INSERT INTO `checkins` (`CheckInID`, `UserID`, `FacilityID`, `Timestamp`, `PointsAwarded`) VALUES
(1, 2, 1, '2026-03-23 08:23:45', 10),
(2, 2, 3, '2026-03-23 08:23:45', 10),
(3, 3, 2, '2026-03-23 08:23:45', 10),
(4, 3, 1, '2026-03-23 08:23:45', 10),
(5, 5, 2, '2026-03-24 06:01:43', 10),
(6, 5, 1, '2026-03-24 06:02:15', 10),
(7, 6, 1, '2026-03-24 09:12:47', 10),
(8, 5, 2, '2026-03-27 17:12:29', 10),
(9, 5, 1, '2026-03-27 17:12:49', 10),
(10, 5, 3, '2026-03-27 17:13:11', 10),
(11, 5, 4, '2026-03-27 17:13:40', 10),
(12, 5, 1, '2026-04-21 05:26:45', 10),
(13, 5, 2, '2026-04-21 05:28:26', 10),
(14, 5, 2, '2026-04-23 18:56:51', 10),
(15, 5, 1, '2026-04-24 07:38:24', 10),
(16, 5, 3, '2026-04-24 08:56:06', 10),
(17, 5, 4, '2026-04-24 15:32:02', 10),
(18, 5, 5, '2026-04-24 16:12:44', 10),
(19, 5, 6, '2026-04-24 17:53:52', 10),
(20, 5, 2, '2026-04-24 18:37:15', 10),
(21, 5, 1, '2026-04-24 18:38:36', 10),
(22, 5, 3, '2026-04-24 19:07:46', 10),
(23, 5, 4, '2026-04-24 19:07:59', 10),
(24, 5, 5, '2026-04-24 19:08:12', 10),
(25, 5, 1, '2026-04-26 07:08:17', 10),
(26, 5, 3, '2026-04-26 07:09:30', 10),
(27, 5, 4, '2026-04-26 07:09:42', 10),
(28, 5, 6, '2026-04-26 07:17:44', 10),
(29, 5, 5, '2026-04-26 07:18:34', 10),
(30, 5, 2, '2026-04-26 08:42:06', 10),
(31, 6, 2, '2026-04-26 13:29:03', 10),
(32, 6, 1, '2026-04-26 13:29:42', 10),
(33, 6, 3, '2026-04-26 13:30:13', 10),
(34, 6, 6, '2026-04-26 13:30:23', 10),
(35, 6, 4, '2026-04-26 13:32:43', 10),
(36, 5, 2, '2026-04-26 18:35:02', 10),
(37, 5, 4, '2026-04-26 18:35:16', 10),
(38, 7, 1, '2026-04-27 06:19:43', 10),
(39, 7, 4, '2026-04-27 06:19:56', 10),
(40, 5, 3, '2026-04-27 07:03:17', 10),
(41, 6, 5, '2026-04-27 07:25:17', 10),
(42, 6, 4, '2026-04-27 07:39:36', 10),
(43, 8, 2, '2026-04-27 08:00:10', 10),
(44, 8, 1, '2026-04-27 08:00:47', 10),
(45, 5, 1, '2026-04-27 08:19:49', 10),
(46, 5, 5, '2026-04-27 08:20:31', 10),
(47, 5, 6, '2026-04-27 08:20:50', 10),
(48, 5, 2, '2026-04-28 10:58:37', 10),
(49, 5, 1, '2026-04-28 10:58:44', 10),
(50, 5, 3, '2026-04-28 10:58:59', 10),
(51, 5, 4, '2026-04-28 10:59:17', 10),
(53, 5, 2, '2026-04-29 03:32:10', 10),
(54, 5, 6, '2026-04-29 03:32:25', 10),
(55, 7, 3, '2026-04-29 04:10:03', 10),
(56, 7, 4, '2026-04-29 04:14:24', 10);

-- --------------------------------------------------------

--
-- Table structure for table `class_bookings`
--

CREATE TABLE `class_bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `booking_date` date DEFAULT curdate(),
  `status` enum('booked','cancelled','attended') DEFAULT 'booked'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_bookings`
--

INSERT INTO `class_bookings` (`booking_id`, `user_id`, `class_id`, `booking_date`, `status`) VALUES
(1, 5, 2, '2026-03-27', 'booked'),
(2, 5, 3, '2026-04-24', 'booked'),
(3, 6, 2, '2026-04-26', 'booked'),
(4, 6, 3, '2026-04-26', 'booked'),
(5, 8, 2, '2026-04-27', 'booked'),
(6, 5, 4, '2026-04-27', 'booked'),
(7, 5, 1, '2026-04-27', 'booked');

-- --------------------------------------------------------

--
-- Table structure for table `class_waitlist`
--

CREATE TABLE `class_waitlist` (
  `waitlist_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('waiting','notified','converted','expired') DEFAULT 'waiting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clubmemberships`
--

CREATE TABLE `clubmemberships` (
  `MembershipID` int(11) NOT NULL,
  `ClubID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `JoinDate` date DEFAULT curdate(),
  `Role` enum('Member','Leader') DEFAULT 'Member',
  `Status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clubmemberships`
--

INSERT INTO `clubmemberships` (`MembershipID`, `ClubID`, `UserID`, `JoinDate`, `Role`, `Status`) VALUES
(1, 1, 2, '2026-03-23', 'Member', 'Active'),
(2, 1, 3, '2026-03-23', 'Member', 'Active'),
(3, 2, 2, '2026-03-23', 'Leader', 'Active'),
(4, 3, 3, '2026-03-23', 'Member', 'Active'),
(5, 1, 5, '2026-03-28', 'Member', 'Inactive'),
(7, 4, 5, '2026-04-24', 'Member', 'Inactive'),
(9, 2, 5, '2026-04-24', 'Member', 'Inactive'),
(11, 3, 5, '2026-04-24', 'Member', 'Inactive'),
(40, 1, 6, '2026-04-26', 'Member', 'Inactive'),
(41, 2, 6, '2026-04-26', 'Member', 'Inactive'),
(45, 3, 6, '2026-04-26', 'Member', 'Inactive'),
(48, 4, 6, '2026-04-26', 'Member', 'Inactive');

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `ClubID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `LeaderID` int(11) DEFAULT NULL,
  `Category` varchar(50) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`ClubID`, `Name`, `Description`, `LeaderID`, `Category`, `CreatedAt`, `Status`) VALUES
(1, 'Coding Club', 'Programming and software development club', NULL, 'Academic', '2026-03-23 08:23:45', 'Active'),
(2, 'Cybersecurity Club', 'Ethical hacking and security enthusiasts', NULL, 'Academic', '2026-03-23 08:23:45', 'Active'),
(3, 'IEEE Student Branch', 'IEEE student chapter', NULL, 'Academic', '2026-03-23 08:23:45', 'Active'),
(4, 'Robotics Club', 'Robotics and automation', NULL, 'Technical', '2026-03-23 08:23:45', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `emergencyalerts`
--

CREATE TABLE `emergencyalerts` (
  `id` int(11) NOT NULL,
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergencyalerts`
--

INSERT INTO `emergencyalerts` (`id`, `severity`, `title`, `message`, `created_at`, `expires_at`, `created_by`, `is_active`) VALUES
(1, 'critical', '⚠️ Fire Drill in Progress', 'A fire drill is scheduled for the Main Building at 2:00 PM. Please follow evacuation procedures.', '2026-03-23 08:23:46', '2026-03-23 15:53:46', NULL, 1),
(2, 'warning', 'Severe Weather Alert', 'Heavy rain expected. Please avoid low-lying areas and use covered walkways.', '2026-03-23 08:23:46', '2026-03-24 01:53:46', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `emergencycontacts`
--

CREATE TABLE `emergencycontacts` (
  `id` int(11) NOT NULL,
  `type` enum('security','medical','support','other') NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergencycontacts`
--

INSERT INTO `emergencycontacts` (`id`, `type`, `name`, `phone`, `description`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'security', 'Campus Security', '011-2345678', '24/7 Emergency Hotline', 1, 1, '2026-03-23 08:23:46'),
(2, 'medical', 'Medical Center', '011-8765432', 'Emergency Medical Services', 2, 1, '2026-03-23 08:23:46'),
(3, 'support', 'Student Support', '011-5678901', 'Counseling & Crisis Support', 3, 1, '2026-03-23 08:23:46'),
(4, 'support', 'IT Support', '011-3456789', 'Technical Emergencies', 4, 1, '2026-03-23 08:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_categories`
--

CREATE TABLE `equipment_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-tag',
  `display_order` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment_categories`
--

INSERT INTO `equipment_categories` (`category_id`, `category_name`, `icon`, `display_order`, `description`, `image_url`) VALUES
(1, 'Cardio', 'fa-heartbeat', 1, NULL, NULL),
(2, 'Strength', 'fa-dumbbell', 2, NULL, NULL),
(3, 'Weight Training', 'fa-weight-hanging', 3, NULL, NULL),
(4, 'Functional', 'fa-person-walking', 4, NULL, NULL),
(5, 'Yoga', 'fa-pray', 5, NULL, NULL),
(6, 'Other', 'fa-tag', 6, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `equipment_issues`
--

CREATE TABLE `equipment_issues` (
  `issue_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `issue_description` text NOT NULL,
  `status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eventbookings`
--

CREATE TABLE `eventbookings` (
  `BookingID` int(11) NOT NULL,
  `EventID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `TicketNumber` varchar(50) NOT NULL,
  `QRCode` text DEFAULT NULL,
  `BookingDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Confirmed','Cancelled','Used') DEFAULT 'Confirmed',
  `CheckInTime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eventlikes`
--

CREATE TABLE `eventlikes` (
  `LikeID` int(11) NOT NULL,
  `EventID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `LikedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `eventlikes`
--

INSERT INTO `eventlikes` (`LikeID`, `EventID`, `UserID`, `LikedAt`) VALUES
(7, 2, 5, '2026-04-28 11:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `EventID` int(11) NOT NULL,
  `Title` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `Location` varchar(200) DEFAULT NULL,
  `StartTime` datetime NOT NULL,
  `EndTime` datetime NOT NULL,
  `OrganizerID` int(11) DEFAULT NULL,
  `Category` enum('SU','Club','Workshop') NOT NULL,
  `Status` enum('Upcoming','Ongoing','Completed','Cancelled') DEFAULT 'Upcoming',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ticket_price` decimal(10,2) DEFAULT 0.00,
  `max_capacity` int(11) DEFAULT 100,
  `booked_count` int(11) DEFAULT 0,
  `event_image` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`EventID`, `Title`, `Description`, `Location`, `StartTime`, `EndTime`, `OrganizerID`, `Category`, `Status`, `CreatedAt`, `ticket_price`, `max_capacity`, `booked_count`, `event_image`) VALUES
(1, 'Hackathon 2024', '24-hour coding competition', 'CS Building', '2026-03-30 13:53:45', '2026-03-31 13:53:45', NULL, 'Club', 'Upcoming', '2026-03-23 08:23:45', 0.00, 100, 0, NULL),
(2, 'Workshop: Web Development', 'Learn modern web development', 'Lab 101', '2026-03-26 13:53:45', '2026-03-26 13:53:45', NULL, 'Workshop', 'Upcoming', '2026-03-23 08:23:45', 0.00, 100, 0, NULL),
(3, 'SU Meeting', 'Student Union general meeting', 'Auditorium', '2026-03-25 13:53:45', '2026-03-25 13:53:45', NULL, 'SU', 'Upcoming', '2026-03-23 08:23:45', 0.00, 100, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `FacilityID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Type` enum('Gym','Library','Café','Transport','GameField','Pool') NOT NULL,
  `Status` enum('Open','Closed','Maintenance') DEFAULT 'Open',
  `Capacity` int(11) DEFAULT NULL,
  `ExtraInfo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ExtraInfo`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`FacilityID`, `Name`, `Type`, `Status`, `Capacity`, `ExtraInfo`) VALUES
(1, 'Main Library', 'Library', 'Open', 500, '{\"floors\": 3, \"wifi\": true, \"air_conditioned\": true}'),
(2, 'University Gym', 'Gym', 'Open', 100, '{\"equipment\": [\"treadmill\", \"weights\"], \"lockers\": true}'),
(3, 'Campus Café', 'Café', 'Open', 150, '{\"menu_available\": true, \"vegetarian_options\": true}'),
(4, 'Shuttle Service', 'Transport', 'Open', 50, '{\"routes\": [\"Malabe\", \"Kaduwela\"], \"frequency\": \"30min\"}'),
(5, 'Sports Field', 'GameField', 'Open', 200, '{\"games\": [\"football\", \"cricket\"], \"lights\": true}'),
(6, 'Olympic Swimming Pool', 'Pool', 'Open', 80, '{\"depth\": \"1.2m-2.5m\", \"lanes\": 8, \"waterTemp\": 27, \"lifeguards\": 4, \"medicalRequired\": true, \"amenities\": [\"Changing Rooms\", \"Showers\", \"Lockers\", \"Pool Equipment\", \"First Aid Station\"]}');

-- --------------------------------------------------------

--
-- Table structure for table `facilityusage`
--

CREATE TABLE `facilityusage` (
  `FacilityID` int(11) DEFAULT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Type` enum('Gym','Library','Café','Transport','GameField','Pool') DEFAULT NULL,
  `TotalCheckIns` bigint(21) DEFAULT NULL,
  `UniqueUsers` bigint(21) DEFAULT NULL,
  `LastCheckIn` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `field_bookings`
--

CREATE TABLE `field_bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(50) DEFAULT NULL,
  `status` enum('booked','cancelled','completed') DEFAULT 'booked',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `field_bookings`
--

INSERT INTO `field_bookings` (`booking_id`, `user_id`, `field_name`, `booking_date`, `time_slot`, `status`, `created_at`) VALUES
(1, 5, 'Basketball Court', '2026-04-24', '9:00 AM - 11:00 AM', 'cancelled', '2026-04-24 17:42:34'),
(2, 5, 'Basketball Court', '2026-04-27', '9:00 AM - 11:00 AM', 'booked', '2026-04-27 08:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `fitness_classes`
--

CREATE TABLE `fitness_classes` (
  `class_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `time` varchar(50) DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT 20,
  `booked` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fitness_classes`
--

INSERT INTO `fitness_classes` (`class_id`, `name`, `time`, `instructor`, `capacity`, `booked`, `created_at`) VALUES
(1, 'Morning Yoga', '8:00 AM', 'Ms. Perera', 20, 13, '2026-03-23 08:23:45'),
(2, 'Zumba Dance', '10:00 AM', 'Mr. Silva', 25, 21, '2026-03-23 08:23:45'),
(3, 'HIIT Workout', '5:00 PM', 'Mr. Fernando', 15, 12, '2026-03-23 08:23:45'),
(4, 'Strength Training', '6:30 PM', 'Ms. Kumari', 12, 6, '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `gamefield`
--

CREATE TABLE `gamefield` (
  `GameID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `GameType` varchar(50) NOT NULL,
  `PointsUsed` int(11) DEFAULT 0,
  `PointsEarned` int(11) DEFAULT 0,
  `Timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `GameData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`GameData`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gamefield`
--

INSERT INTO `gamefield` (`GameID`, `UserID`, `GameType`, `PointsUsed`, `PointsEarned`, `Timestamp`, `GameData`) VALUES
(1, 2, 'Quiz Challenge', 0, 20, '2026-03-23 08:23:45', NULL),
(2, 2, 'AR Treasure Hunt', 10, 15, '2026-03-23 08:23:45', NULL),
(3, 3, 'Memory Game', 5, 10, '2026-03-23 08:23:45', NULL),
(4, 5, 'Math Game', 0, 174, '2026-04-27 08:22:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gps_checkin_history`
--

CREATE TABLE `gps_checkin_history` (
  `checkin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `facility_name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `checkin_date` date NOT NULL,
  `checkin_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gym_announcements`
--

CREATE TABLE `gym_announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gym_announcements`
--

INSERT INTO `gym_announcements` (`announcement_id`, `title`, `message`, `is_active`, `created_by`, `created_at`, `expires_at`) VALUES
(1, 'hn', 'n', 1, 12, '2026-04-22 12:28:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gym_equipment`
--

CREATE TABLE `gym_equipment` (
  `equipment_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `available` int(11) DEFAULT 1,
  `category` varchar(100) DEFAULT NULL,
  `image_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gym_equipment`
--

INSERT INTO `gym_equipment` (`equipment_id`, `name`, `quantity`, `available`, `category`, `image_id`, `created_at`) VALUES
(1, 'Treadmill', 5, 3, 'Cardio', 'treadmill.jpg', '2026-03-23 08:23:45'),
(2, 'Dumbbells Set', 20, 15, 'Strength', 'dumbbell.jpg', '2026-03-23 08:23:45'),
(3, 'Bench Press', 4, 2, 'Strength', 'bench_press.jpg', '2026-03-23 08:23:45'),
(4, 'Pull-up Bar', 3, 3, 'Strength', 'pull_up_bar.jpg', '2026-03-23 08:23:45'),
(5, 'Exercise Bike', 4, 1, 'Cardio', 'exercise_bike.jpg', '2026-03-23 08:23:45'),
(6, 'Rowing Machine', 2, 1, 'Cardio', 'rowing_machine.jpg', '2026-03-23 08:23:45'),
(7, 'Yoga Mats', 15, 8, 'Accessories', 'yoga_mat.jpg', '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `gym_operating_hours`
--

CREATE TABLE `gym_operating_hours` (
  `id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `is_closed` tinyint(1) DEFAULT 0,
  `special_note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gym_operating_hours`
--

INSERT INTO `gym_operating_hours` (`id`, `day_of_week`, `open_time`, `close_time`, `is_closed`, `special_note`) VALUES
(1, 'Monday', '06:00:00', '22:00:00', 0, NULL),
(2, 'Tuesday', '06:00:00', '22:00:00', 0, NULL),
(3, 'Wednesday', '06:00:00', '22:00:00', 0, NULL),
(4, 'Thursday', '06:00:00', '22:00:00', 0, NULL),
(5, 'Friday', '06:00:00', '21:00:00', 0, NULL),
(6, 'Saturday', '08:00:00', '20:00:00', 0, NULL),
(7, 'Sunday', '08:00:00', '18:00:00', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gym_settings`
--

CREATE TABLE `gym_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gym_settings`
--

INSERT INTO `gym_settings` (`setting_id`, `setting_key`, `setting_value`, `description`) VALUES
(1, 'points_per_class', '5', 'Points awarded for joining a class'),
(2, 'max_classes_per_day', '3', 'Maximum classes a user can book per day'),
(3, 'cancellation_window_hours', '2', 'Hours before class to cancel without penalty'),
(4, 'waitlist_enabled', '1', 'Enable/disable waitlist feature'),
(5, 'bonus_points_attendance', '5', 'Bonus points for attending class (QR check-in)');

-- --------------------------------------------------------

--
-- Table structure for table `gym_status`
--

CREATE TABLE `gym_status` (
  `id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `closing_time` varchar(20) DEFAULT '10:00 PM',
  `pool_available` tinyint(1) DEFAULT 1,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_status`
--

INSERT INTO `gym_status` (`id`, `status`, `closing_time`, `pool_available`, `last_updated`) VALUES
(1, 'Open', '10:00 PM', 1, '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `importhistory`
--

CREATE TABLE `importhistory` (
  `ImportID` int(11) NOT NULL,
  `FileName` varchar(255) NOT NULL,
  `RecordsImported` int(11) DEFAULT 0,
  `Status` varchar(50) DEFAULT 'Success',
  `ImportedBy` int(11) DEFAULT NULL,
  `ImportedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `joinrequests`
--

CREATE TABLE `joinrequests` (
  `RequestID` int(11) NOT NULL,
  `ClubID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `RequestDate` datetime DEFAULT current_timestamp(),
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `AdminNotes` text DEFAULT NULL,
  `ReviewedBy` int(11) DEFAULT NULL,
  `ReviewedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `joinrequests`
--

INSERT INTO `joinrequests` (`RequestID`, `ClubID`, `UserID`, `RequestDate`, `Status`, `AdminNotes`, `ReviewedBy`, `ReviewedAt`) VALUES
(1, 5, 12, '2026-04-20 21:18:24', 'Approved', '', 12, '2026-04-20 21:18:57'),
(3, 4, 12, '2026-04-20 21:24:34', 'Approved', '', 12, '2026-04-20 21:25:07'),
(4, 2, 12, '2026-04-20 21:28:29', 'Pending', NULL, NULL, NULL),
(5, 3, 12, '2026-04-21 12:13:22', 'Approved', '', 12, '2026-04-21 12:13:45'),
(6, 2, 7, '2026-04-26 15:28:56', 'Rejected', '', 7, '2026-04-27 23:38:22'),
(0, 1, 5, '2026-04-27 23:34:53', 'Rejected', '', 7, '2026-04-27 23:38:39'),
(0, 2, 5, '2026-04-27 23:34:58', 'Rejected', '', 7, '2026-04-27 23:38:39'),
(0, 1, 7, '2026-04-29 09:45:27', 'Pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lifeguards`
--

CREATE TABLE `lifeguards` (
  `lifeguard_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `certification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT 0,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lifeguards`
--

INSERT INTO `lifeguards` (`lifeguard_id`, `name`, `email`, `phone`, `certification`, `experience`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Kumara Sangakra', 'Kumara.doe@example.com', '0771234567', 'Lifeguard Certificate', 5, 'active', '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(2, 'John kells', 'john.smith@example.com', '0772345678', 'Water Safety Instructor', 3, 'active', '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(3, 'Mike Johnson', 'mike.johnson@example.com', '0773456789', 'CPR Instructor', 7, 'active', '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(4, 'Sanath jayasuriya ', 'sanat.wilson@example.com', '0774567890', 'First Aid', 2, 'active', '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(5, 'Tom Brown', 'tom.brown@example.com', '0775678901', 'Pool Operator', 4, 'on_leave', '2026-03-23 08:23:45', '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `lifeguard_schedule`
--

CREATE TABLE `lifeguard_schedule` (
  `schedule_id` int(11) NOT NULL,
  `lifeguard_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `time_slot` varchar(20) NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location_checkins`
--

CREATE TABLE `location_checkins` (
  `checkin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `checkin_type` enum('gps','qr') NOT NULL,
  `points_earned` int(11) DEFAULT 10,
  `checkin_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `checkin_date` date GENERATED ALWAYS AS (cast(`checkin_time` as date)) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location_checkins`
--

INSERT INTO `location_checkins` (`checkin_id`, `user_id`, `location_id`, `checkin_type`, `points_earned`, `checkin_time`) VALUES
(3, 1, 32, 'gps', 10, '2026-03-18 03:03:15');

-- --------------------------------------------------------

--
-- Table structure for table `loginstreaks`
--

CREATE TABLE `loginstreaks` (
  `StreakID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `CurrentStreak` int(11) DEFAULT 0,
  `LongestStreak` int(11) DEFAULT 0,
  `LastLoginDate` date NOT NULL,
  `TotalLogins` int(11) DEFAULT 0,
  `StreakFreezes` int(11) DEFAULT 0 COMMENT 'Days that can be missed without breaking streak',
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_reports`
--

CREATE TABLE `medical_reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_date` date DEFAULT curdate(),
  `expiry_date` date DEFAULT NULL,
  `is_valid` tinyint(1) DEFAULT 1,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membershiptiers`
--

CREATE TABLE `membershiptiers` (
  `TierID` int(11) NOT NULL,
  `TierName` varchar(50) NOT NULL,
  `MinPoints` int(11) NOT NULL,
  `MaxPoints` int(11) DEFAULT NULL,
  `Multiplier` decimal(3,2) DEFAULT 1.00,
  `Benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Benefits`)),
  `Color` varchar(20) DEFAULT '#667eea',
  `Icon` varchar(50) DEFAULT 'fa-star',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membershiptiers`
--

INSERT INTO `membershiptiers` (`TierID`, `TierName`, `MinPoints`, `MaxPoints`, `Multiplier`, `Benefits`, `Color`, `Icon`, `CreatedAt`) VALUES
(1, 'Bronze', 0, 499, 1.00, '{\"points_multiplier\": 1.0}', '#CD7F32', 'fa-bronze', '2026-03-23 08:23:45'),
(2, 'Silver', 500, 1999, 1.20, '{\"points_multiplier\": 1.2, \"free_drinks\": 1}', '#C0C0C0', 'fa-silver', '2026-03-23 08:23:45'),
(3, 'Gold', 2000, 4999, 1.50, '{\"points_multiplier\": 1.5, \"free_drinks\": 2, \"priority_booking\": true}', '#FFD700', 'fa-gold', '2026-03-23 08:23:45'),
(4, 'Platinum', 5000, NULL, 2.00, '{\"points_multiplier\": 2.0, \"free_drinks\": 4, \"priority_booking\": true, \"free_events\": true}', '#E5E4E2', 'fa-crown', '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `menuitems`
--

CREATE TABLE `menuitems` (
  `ItemID` int(11) NOT NULL,
  `ItemName` varchar(100) NOT NULL,
  `Category` enum('Food','Beverage','Dessert') NOT NULL,
  `BasePrice` decimal(10,2) NOT NULL,
  `CurrentPrice` decimal(10,2) NOT NULL,
  `Status` enum('Available','Unavailable') DEFAULT 'Available',
  `Description` text DEFAULT NULL,
  `ImageURL` varchar(500) DEFAULT NULL,
  `TimesOrdered` int(11) DEFAULT 0,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notificationlog`
--

CREATE TABLE `notificationlog` (
  `LogID` int(11) NOT NULL,
  `NotificationID` int(11) DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `Type` enum('gym','event','transport','general','emergency') NOT NULL,
  `TargetType` enum('all','user_group','location','tier') NOT NULL,
  `TargetValue` varchar(255) DEFAULT NULL,
  `ScheduledFor` datetime DEFAULT NULL,
  `SentAt` datetime DEFAULT NULL,
  `Status` enum('scheduled','sending','sent','failed','cancelled') DEFAULT 'scheduled',
  `SentCount` int(11) DEFAULT 0,
  `FailedCount` int(11) DEFAULT 0,
  `OpenedCount` int(11) DEFAULT 0,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notificationlog`
--

INSERT INTO `notificationlog` (`LogID`, `NotificationID`, `Title`, `Message`, `Type`, `TargetType`, `TargetValue`, `ScheduledFor`, `SentAt`, `Status`, `SentCount`, `FailedCount`, `OpenedCount`, `CreatedBy`, `CreatedAt`) VALUES
(1, NULL, '🚨 URGENT: ', 'Hi', 'emergency', 'all', '', NULL, '2026-04-29 15:28:15', 'sent', 6, 0, 0, 7, '2026-04-29 09:58:15');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('gym','event','transport','general','reminder') DEFAULT 'general',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Hackathon Reminder', 'Reminder: Hackathon registration closes tomorrow', 'event', 0, '2026-03-23 08:23:45'),
(2, 2, 'Transport Pass Expiry', 'Your transport pass expires in 7 days', 'transport', 0, '2026-03-23 08:23:45'),
(3, 3, 'Club Meeting Today', 'Club meeting today at 3 PM', 'event', 0, '2026-03-23 08:23:45'),
(5, 2, 'Points Earned', 'You earned 20 points from Memory Game!', 'general', 0, '2026-03-23 08:23:45'),
(6, 5, 'Welcome to Synergy Hub!', 'Thank you for joining. Explore our facilities and earn points!', 'general', 1, '2026-04-27 06:47:05'),
(7, 5, 'Gym Special Offer', 'Get 20% off on gym membership this week only!', 'gym', 1, '2026-04-26 06:47:05'),
(8, 5, 'Transport Schedule Updated', 'New bus schedule has been published. Check it out!', 'transport', 1, '2026-04-25 06:47:05'),
(9, 5, 'Coding Club Meeting', 'Coding Club meeting tomorrow at 3 PM in Lab 101', 'event', 1, '2026-04-24 06:47:05'),
(10, 6, 'Welcome to Synergy Hub!', 'Thank you for joining. Explore our facilities and earn points!', 'general', 1, '2026-04-27 06:47:05'),
(11, 6, 'Gym Status', 'Gym is open from 6 AM to 10 PM today', 'gym', 1, '2026-04-26 06:47:05'),
(13, 2, '🚨 URGENT: ', 'Hi', '', 0, '2026-04-29 09:58:15'),
(14, 3, '🚨 URGENT: ', 'Hi', '', 0, '2026-04-29 09:58:15'),
(15, 5, '🚨 URGENT: ', 'Hi', '', 0, '2026-04-29 09:58:15'),
(16, 6, '🚨 URGENT: ', 'Hi', '', 0, '2026-04-29 09:58:15'),
(17, 8, '🚨 URGENT: ', 'Hi', '', 0, '2026-04-29 09:58:15'),
(18, 10, '🚨 URGENT: ', 'Hi', '', 0, '2026-04-29 09:58:15');

-- --------------------------------------------------------

--
-- Table structure for table `notificationtemplates`
--

CREATE TABLE `notificationtemplates` (
  `TemplateID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `Type` enum('gym','event','transport','general','emergency') DEFAULT 'general',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ordernotes`
--

CREATE TABLE `ordernotes` (
  `NoteID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Note` text NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `IsAdminNote` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ItemName` varchar(100) NOT NULL,
  `Category` enum('Food','Beverage','Dessert') NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Quantity` int(11) DEFAULT 1,
  `Timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Pending','Preparing','Ready','Completed','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderID`, `UserID`, `ItemName`, `Category`, `Price`, `Quantity`, `Timestamp`, `Status`) VALUES
(1, 2, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-03-23 08:23:45', 'Completed'),
(2, 2, 'Coffee', 'Beverage', 250.00, 2, '2026-03-23 08:23:45', 'Preparing'),
(3, 3, 'Chocolate Cake', 'Dessert', 350.00, 1, '2026-03-23 08:23:45', 'Pending'),
(4, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:18:03', 'Pending'),
(5, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:18:03', 'Pending'),
(6, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:18:15', 'Pending'),
(7, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:18:15', 'Pending'),
(8, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:20:44', 'Pending'),
(9, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:20:44', 'Pending'),
(12, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:21:24', 'Pending'),
(13, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:21:24', 'Pending'),
(14, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:40:31', 'Pending'),
(15, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:40:31', 'Pending'),
(16, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:40:34', 'Pending'),
(17, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:40:34', 'Pending'),
(18, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:40:59', 'Pending'),
(19, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:40:59', 'Pending'),
(20, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:42:12', 'Pending'),
(21, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:42:12', 'Pending'),
(22, 5, 'Chicken Sandwich', 'Food', 450.00, 1, '2026-04-24 15:46:27', 'Pending'),
(23, 5, 'Pasta', 'Food', 480.00, 1, '2026-04-24 15:52:06', 'Pending'),
(24, 5, 'Pasta', 'Food', 480.00, 1, '2026-04-24 15:52:27', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `orderstatuslog`
--

CREATE TABLE `orderstatuslog` (
  `LogID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `OldStatus` enum('Pending','Preparing','Ready','Completed','Cancelled') DEFAULT NULL,
  `NewStatus` enum('Pending','Preparing','Ready','Completed','Cancelled') NOT NULL,
  `ChangedBy` int(11) NOT NULL,
  `ChangedAt` datetime DEFAULT current_timestamp(),
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orderstatuslog`
--

INSERT INTO `orderstatuslog` (`LogID`, `OrderID`, `OldStatus`, `NewStatus`, `ChangedBy`, `ChangedAt`, `Notes`) VALUES
(3, 16, 'Pending', 'Preparing', 12, '2026-04-20 22:03:16', 'Status changed by admin'),
(4, 15, 'Pending', 'Preparing', 12, '2026-04-20 22:04:34', 'Status changed by admin'),
(5, 15, 'Preparing', 'Ready', 12, '2026-04-20 22:04:50', 'Status changed by admin'),
(6, 16, 'Preparing', 'Ready', 12, '2026-04-20 22:05:01', 'Status changed by admin'),
(7, 11, 'Pending', 'Preparing', 12, '2026-04-20 22:05:27', 'Status changed by admin'),
(8, 11, 'Preparing', 'Ready', 12, '2026-04-20 22:05:31', 'Status changed by admin'),
(9, 14, 'Pending', 'Preparing', 12, '2026-04-20 22:05:42', 'Status changed by admin'),
(10, 14, 'Preparing', 'Ready', 12, '2026-04-20 22:05:44', 'Status changed by admin'),
(11, 11, 'Ready', 'Completed', 12, '2026-04-20 22:06:00', 'Status changed by admin'),
(12, 16, 'Ready', 'Completed', 12, '2026-04-20 22:06:21', 'Status changed by admin'),
(13, 17, 'Pending', 'Completed', 12, '2026-04-20 22:26:09', NULL),
(14, 18, 'Pending', 'Preparing', 12, '2026-04-21 12:15:52', 'Status changed by admin'),
(15, 18, 'Preparing', 'Ready', 12, '2026-04-21 12:16:03', 'Status changed by admin'),
(16, 14, 'Ready', 'Completed', 12, '2026-04-21 12:16:11', 'Status changed by admin'),
(17, 15, 'Ready', 'Completed', 12, '2026-04-21 12:16:26', 'Status changed by admin'),
(18, 18, 'Ready', 'Completed', 12, '2026-04-21 12:16:29', 'Status changed by admin');

-- --------------------------------------------------------

--
-- Table structure for table `panorama_images`
--

CREATE TABLE `panorama_images` (
  `panorama_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `heading_offset` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pointsconfig`
--

CREATE TABLE `pointsconfig` (
  `ConfigID` int(11) NOT NULL,
  `ActionType` varchar(50) NOT NULL,
  `Points` int(11) NOT NULL DEFAULT 0,
  `Description` text DEFAULT NULL,
  `MaxPerDay` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pointsconfig`
--

INSERT INTO `pointsconfig` (`ConfigID`, `ActionType`, `Points`, `Description`, `MaxPerDay`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'LOGIN', 10, 'Daily login bonus', NULL, '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(2, 'FACILITY_VISIT', 20, 'Check in to a facility', NULL, '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(3, 'EVENT_ATTENDANCE', 50, 'Attend a campus event', NULL, '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(4, 'BOOK_BORROW', 5, 'Borrow a book from library', NULL, '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(5, 'GAME_PLAY', 15, 'Play a game', NULL, '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(6, 'CLUB_JOIN', 20, 'Join a club', NULL, '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(7, 'REFERRAL', 100, 'Refer a friend', NULL, '2026-03-23 08:23:45', '2026-03-23 08:23:45'),
(8, 'ADMIN_ADJUSTMENT', 0, 'Manual adjustment by admin', NULL, '2026-03-23 08:23:45', '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `pointsexpiration`
--

CREATE TABLE `pointsexpiration` (
  `ExpirationID` int(11) NOT NULL,
  `ExpiryDays` int(11) DEFAULT 365,
  `LastEarnedPointsExpire` tinyint(1) DEFAULT 1,
  `NotificationDays` int(11) DEFAULT 30,
  `Enabled` tinyint(1) DEFAULT 1,
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pointsexpiration`
--

INSERT INTO `pointsexpiration` (`ExpirationID`, `ExpiryDays`, `LastEarnedPointsExpire`, `NotificationDays`, `Enabled`, `UpdatedAt`) VALUES
(1, 365, 1, 30, 1, '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `pointshistory`
--

CREATE TABLE `pointshistory` (
  `HistoryID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `PointsChange` int(11) NOT NULL,
  `ActionType` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pointshistory`
--

INSERT INTO `pointshistory` (`HistoryID`, `UserID`, `PointsChange`, `ActionType`, `Description`, `CreatedAt`) VALUES
(1, 5, 20, 'FACILITY_VISIT', 'QR Scan: Gym (Tier Multiplier: 1x)', '2026-04-28 17:20:37'),
(2, 5, 5, 'BOOK_BORROW', 'QR Scan: Library (Tier Multiplier: 1x)', '2026-04-28 17:20:46'),
(3, 5, 20, 'FACILITY_VISIT', 'QR Scan: Gym (Tier Multiplier: 1x)', '2026-04-28 17:20:49'),
(4, 5, 5, 'BOOK_BORROW', 'QR Scan: Library (Tier Multiplier: 1x)', '2026-04-28 17:20:51'),
(5, 5, 20, 'FACILITY_VISIT', 'QR Scan: Café (Tier Multiplier: 1x)', '2026-04-28 17:20:52'),
(6, 5, 50, 'EVENT_ATTENDANCE', 'QR Scan: Event (Tier Multiplier: 1x)', '2026-04-28 17:20:54'),
(7, 5, 15, 'GAME_PLAY', 'QR Scan: Game (Tier Multiplier: 1x)', '2026-04-28 17:20:56'),
(8, 5, 5, 'BOOK_BORROW', 'QR Scan: Library (Tier Multiplier: 1x)', '2026-04-28 17:26:59'),
(9, 5, 50, 'EVENT_ATTENDANCE', 'QR Scan: Event (Tier Multiplier: 1x)', '2026-04-28 17:27:06'),
(10, 5, 15, 'GAME_PLAY', 'QR Scan: Game (Tier Multiplier: 1x)', '2026-04-28 17:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `pool_bookings`
--

CREATE TABLE `pool_bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `lane_number` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'confirmed',
  `medical_report_id` int(11) DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qr_scan_history`
--

CREATE TABLE `qr_scan_history` (
  `scan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `location_type` varchar(100) NOT NULL,
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `scan_date` date NOT NULL,
  `scan_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `qr_scan_history`
--

INSERT INTO `qr_scan_history` (`scan_id`, `user_id`, `qr_code`, `location_type`, `points_earned`, `scan_date`, `scan_time`, `created_at`) VALUES
(1, 5, 'GYM001', 'Gym', 20, '0000-00-00', '22:50:37', '2026-04-28 17:20:37'),
(2, 5, 'LIB001', 'Library', 5, '0000-00-00', '22:50:46', '2026-04-28 17:20:46'),
(5, 5, 'CAFE001', 'Café', 20, '0000-00-00', '22:50:52', '2026-04-28 17:20:52'),
(6, 5, 'EVENT001', 'Event', 50, '0000-00-00', '22:50:54', '2026-04-28 17:20:54'),
(7, 5, 'GAME001', 'Game', 15, '0000-00-00', '22:50:56', '2026-04-28 17:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `RewardID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `PointsRequired` int(11) NOT NULL,
  `Availability` enum('Available','Limited','Out of Stock') DEFAULT 'Available',
  `Quantity` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`RewardID`, `Name`, `Description`, `PointsRequired`, `Availability`, `Quantity`, `CreatedAt`) VALUES
(1, 'Free Café Coffee', 'Redeem for one free coffee at campus café', 50, 'Available', 100, '2026-03-23 08:23:45'),
(2, 'Transport Pass', 'One-day unlimited transport pass', 100, 'Available', 50, '2026-03-23 08:23:45'),
(3, 'Gym Day Pass', 'One-day gym access', 75, 'Available', 30, '2026-03-23 08:23:45'),
(4, 'University Merch', 'University t-shirt', 200, 'Limited', 10, '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `rewardsredemption`
--

CREATE TABLE `rewardsredemption` (
  `RedemptionID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `RewardID` int(11) NOT NULL,
  `PointsSpent` int(11) NOT NULL,
  `Status` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
  `RedeemedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `CompletedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `safetyincidents`
--

CREATE TABLE `safetyincidents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `severity` enum('low','medium','high') DEFAULT 'medium',
  `report_date` datetime NOT NULL,
  `status` enum('active','resolved','investigating') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `safetyincidents`
--

INSERT INTO `safetyincidents` (`id`, `title`, `description`, `location`, `severity`, `report_date`, `status`, `created_at`) VALUES
(1, 'Minor Fire in Science Lab', 'Small chemical fire contained quickly. No injuries. Lab evacuated safely.', 'Science Building, Lab 203', 'medium', '2026-03-21 13:53:46', 'resolved', '2026-03-23 08:23:46'),
(2, 'Suspicious Person Reported', 'Unknown person attempting to access dormitories. Security responded.', 'Student Housing, Block C', 'high', '2026-03-18 13:53:46', 'investigating', '2026-03-23 08:23:46'),
(3, 'Power Outage', 'Short power outage in Library. Emergency lighting worked properly.', 'Main Library', 'low', '2026-03-16 13:53:46', 'resolved', '2026-03-23 08:23:46'),
(4, 'Medical Emergency', 'Student fainted in cafeteria. Treated by campus medical team.', 'Student Center', 'medium', '2026-03-13 13:53:46', 'resolved', '2026-03-23 08:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `safetyquiz`
--

CREATE TABLE `safetyquiz` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_answer` int(11) NOT NULL,
  `explanation` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `safetyquiz`
--

INSERT INTO `safetyquiz` (`id`, `question`, `options`, `correct_answer`, `explanation`, `category`, `difficulty`, `is_active`, `created_at`) VALUES
(1, 'What is the first thing you should do in case of a fire?', '[\"Grab your belongings\", \"Call your parents\", \"Alert others and evacuate\", \"Try to put out the fire yourself\"]', 2, 'Alerting others and evacuating immediately is crucial. Your safety is more important than belongings. Fires spread quickly - every second counts.', 'Fire Safety', 'easy', 1, '2026-03-23 08:23:46'),
(2, 'What is the campus security emergency number?', '[\"011-1234567\", \"011-2345678\", \"011-3456789\", \"011-4567890\"]', 1, 'Campus Security can be reached 24/7 at 011-2345678. Save this number in your phone right now. It could save your life or someone else\'s.', 'Emergency Contacts', 'easy', 1, '2026-03-23 08:23:46'),
(3, 'Which of these is NOT a good password practice?', '[\"Using different passwords for each account\", \"Enabling two-factor authentication\", \"Using your birthday as password\", \"Changing passwords every 3 months\"]', 2, 'Never use personal information like birthdays, names, or common words as passwords. Hackers can easily guess these. Use a password manager instead.', 'Cyber Safety', 'medium', 1, '2026-03-23 08:23:46'),
(4, 'What should you do if someone is having a medical emergency?', '[\"Take a video for evidence\", \"Call for help and stay with them\", \"Move them to a comfortable position\", \"Give them water or food\"]', 1, 'Call for emergency help immediately and stay with the person. Do not move them unless they are in immediate danger. Keep them calm and comfortable until help arrives.', 'Medical Emergency', 'medium', 1, '2026-03-23 08:23:46'),
(5, 'Where should you assemble after evacuating a building?', '[\"In the parking lot near your car\", \"At the designated assembly point\", \"At the nearest cafe or restaurant\", \"Go home immediately\"]', 1, 'Always go to the designated assembly point so authorities can account for everyone. This helps ensure no one is trapped inside and rescue efforts can be focused.', 'Fire Safety', 'easy', 1, '2026-03-23 08:23:46'),
(6, 'What should you do if you receive a suspicious email?', '[\"Click links to check if they work\", \"Reply and ask who sent it\", \"Delete it without opening\", \"Forward to all your friends as warning\"]', 2, 'Delete suspicious emails immediately without opening any links or attachments. Report to IT if it appears to come from a campus source. Never engage with potential phishing attempts.', 'Cyber Safety', 'medium', 1, '2026-03-23 08:23:46'),
(7, 'How can you request a safe walk escort?', '[\"Call campus security\", \"Use mobile safety app\", \"Ask campus police\", \"All of the above\"]', 3, 'Campus security is the primary method (011-2345678), but you can also use safety apps or ask campus police. The service is free and available 24/7 for all students and staff.', 'Personal Safety', 'easy', 1, '2026-03-23 08:23:46'),
(8, 'What should be in your personal emergency kit?', '[\"Water and snacks\", \"First aid supplies\", \"Flashlight and batteries\", \"All of the above\"]', 3, 'A complete emergency kit should include: water (1 gallon per person/day), non-perishable food, first aid kit, flashlight, batteries, whistle, dust masks, wet wipes, and important documents.', 'Emergency Preparedness', 'medium', 1, '2026-03-23 08:23:46'),
(9, 'How often should you update your emergency contacts?', '[\"Never, once set\", \"Every semester\", \"Every year or when they change\", \"Only during orientation\"]', 2, 'Review and update your emergency contacts at least once a year, or whenever your contacts change (new phone numbers, etc.). Keep both digital and written copies.', 'Personal Safety', 'easy', 1, '2026-03-23 08:23:46'),
(10, 'What is the correct way to use a fire extinguisher?', '[\"Spray and run\", \"PULL, AIM, SQUEEZE, SWEEP\", \"Throw it at the fire\", \"Call someone to use it\"]', 1, 'Remember PASS: PULL the pin, AIM at the base of the fire, SQUEEZE the handle, SWEEP from side to side. Only use if fire is small and you have clear exit path.', 'Fire Safety', 'hard', 1, '2026-03-23 08:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `safetytips`
--

CREATE TABLE `safetytips` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `tags` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `safetytips`
--

INSERT INTO `safetytips` (`id`, `title`, `content`, `category`, `priority`, `tags`, `image_url`, `video_url`, `views`, `likes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Fire Evacuation Procedures', 'In case of fire: 1. Stay calm 2. Alert others 3. Use nearest exit 4. Do not use elevators 5. Assemble at designated meeting point 6. Wait for further instructions. Remember: Your safety is more important than belongings. Never re-enter a burning building.', 'Fire Safety', 'high', 'fire,evacuation,emergency,safety', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(2, 'Campus Safe Walk Program', 'The Safe Walk program provides escort services between 6 PM and 6 AM. Call security at 011-2345678 to request an escort. Available for all students and staff. Wait in well-lit areas for the escort. Have your ID ready.', 'Personal Safety', 'medium', 'walk,night,safety,escort,security', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(3, 'Cyber Security Best Practices', 'Use strong passwords (mix of letters, numbers, symbols). Enable two-factor authentication on all accounts. Avoid public WiFi for banking. Don\'t click suspicious links. Keep software updated. Report phishing to IT.', 'Cyber Safety', 'high', 'cyber,security,password,phishing,hacking', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(4, 'Medical Emergency Response', 'For medical emergencies: Call 011-8765432 immediately. Provide: your location, what happened, number of people injured. Do not move the person unless danger. Stay on line. Apply pressure to bleeding. Know CPR.', 'Medical Emergency', 'high', 'medical,firstaid,emergency,cpr', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(5, 'Lab Safety Guidelines', 'Always wear PPE: goggles, gloves, lab coat. Know emergency exits and eyewash stations. Report spills immediately. Follow chemical handling procedures. No food or drinks. Never work alone after hours.', 'Lab Safety', 'high', 'lab,science,chemicals,ppe', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(6, 'Bus Safety Tips', 'Wait at designated stops. Board and exit carefully. Remain seated while moving. Keep aisles clear. Hold handrails. Report suspicious activity to driver. Know your stop. Be aware of surroundings.', 'Travel Safety', 'medium', 'bus,transport,safety,commute', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(7, 'Earthquake Preparedness', 'DROP to hands and knees. COVER head and neck under sturdy table. HOLD ON until shaking stops. Stay away from windows. If outdoors, move to open area. After shaking, evacuate carefully. Watch for falling debris.', 'Emergency Preparedness', 'high', 'earthquake,natural disaster,emergency', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(8, 'Personal Safety at Night', 'Travel in groups of 2+. Stay in well-lit areas. Keep phone charged. Share location with friends. Trust your instincts. Know emergency phone locations. Avoid shortcuts. Keep keys ready. Be aware.', 'Personal Safety', 'high', 'night,safety,walking,campus', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(9, 'Mental Health Resources', '24/7 Counseling: 011-5678901. Walk-in: Mon-Fri 9 AM-5 PM at Student Services. Free and confidential. Support groups available. Online resources. Don\'t struggle alone. Reach out for help.', 'Health & Wellness', 'medium', 'mental health,counseling,support,wellness', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46'),
(10, 'Lost and Found Procedures', 'Report lost items to Security Office (Building A, Room 101). Check online portal. Label belongings. For valuable items (laptops, phones, wallets), file police report. Check within 24 hours.', 'Campus Services', 'low', 'lost,found,property,security', NULL, NULL, 0, 0, 1, '2026-03-23 08:23:46', '2026-03-23 08:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `special_offers`
--

CREATE TABLE `special_offers` (
  `offer_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','points','bogo') DEFAULT 'percentage',
  `discount_value` int(11) DEFAULT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `offer_price` decimal(10,2) DEFAULT NULL,
  `points_required` int(11) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `special_offers`
--

INSERT INTO `special_offers` (`offer_id`, `facility_id`, `title`, `description`, `discount_type`, `discount_value`, `original_price`, `offer_price`, `points_required`, `valid_from`, `valid_until`, `image_url`, `is_active`, `created_at`) VALUES
(1, 3, 'Happy Hour Special', 'Get 20% off on all beverages from 4 PM to 6 PM', 'percentage', 20, NULL, NULL, 0, NULL, '2026-04-22', 'https://images.unsplash.com/photo-1470119693884-47d3a1d1f180?w=300&h=200&fit=crop', 1, '2026-03-23 08:23:45'),
(2, 3, 'Combo Meal Deal', 'Chicken Rice + Soft Drink for only 450 points', 'points', 450, 550.00, NULL, 450, NULL, '2026-04-07', 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=300&h=200&fit=crop', 1, '2026-03-23 08:23:45'),
(3, 3, 'Buy 1 Get 1 Free', 'Buy any coffee and get one free', 'bogo', 1, 250.00, NULL, 0, NULL, '2026-03-30', 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=300&h=200&fit=crop', 1, '2026-03-23 08:23:45'),
(4, 3, 'Cake', 'Discount', 'bogo', 0, 0.00, 0.00, 100, '2026-04-29', '2026-05-29', NULL, 1, '2026-04-29 04:09:51'),
(5, 3, 'Banana Cake', 'Yummm', 'percentage', 10, 100.00, 90.02, 100, '2026-04-29', '2026-05-29', NULL, 1, '2026-04-29 04:11:35');

-- --------------------------------------------------------

--
-- Table structure for table `streakbonuses`
--

CREATE TABLE `streakbonuses` (
  `StreakID` int(11) NOT NULL,
  `StreakType` enum('Daily','Weekly') NOT NULL,
  `StreakDays` int(11) NOT NULL,
  `Multiplier` decimal(3,2) DEFAULT 1.00,
  `BonusPoints` int(11) DEFAULT 0,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `streakbonuses`
--

INSERT INTO `streakbonuses` (`StreakID`, `StreakType`, `StreakDays`, `Multiplier`, `BonusPoints`, `Description`) VALUES
(1, 'Daily', 7, 1.50, 50, '7-day daily streak bonus'),
(2, 'Daily', 30, 2.00, 200, '30-day daily streak bonus'),
(3, 'Weekly', 4, 1.20, 100, '4-week weekly streak bonus');

-- --------------------------------------------------------

--
-- Table structure for table `study_rooms`
--

CREATE TABLE `study_rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `capacity` int(11) DEFAULT 4,
  `has_projector` tinyint(1) DEFAULT 0,
  `has_whiteboard` tinyint(1) DEFAULT 1,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `study_rooms`
--

INSERT INTO `study_rooms` (`room_id`, `room_name`, `capacity`, `has_projector`, `has_whiteboard`, `is_available`, `created_at`) VALUES
(1, 'Study Room 1', 4, 1, 1, 1, '2026-03-23 08:23:45'),
(2, 'Study Room 2', 6, 1, 1, 1, '2026-03-23 08:23:45'),
(3, 'Group Study Hall', 10, 1, 1, 1, '2026-03-23 08:23:45'),
(4, 'Quiet Reading Room', 2, 0, 0, 1, '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `su_events`
--

CREATE TABLE `su_events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_time` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `su_events`
--

INSERT INTO `su_events` (`event_id`, `title`, `event_time`, `location`, `description`, `created_at`) VALUES
(1, 'Quiz Night', '2026-03-25 13:53:45', 'Main Hall', 'Test your knowledge!', '2026-03-23 08:23:45'),
(2, 'SU Meeting', '2026-03-24 13:53:45', 'Room 101', 'Weekly student union meeting', '2026-03-23 08:23:45'),
(3, 'Movie Night', '2026-03-26 13:53:45', 'Lecture Theatre', 'Free movie screening', '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'theme', 'dark', '2026-04-22 12:36:26'),
(2, 'primary_color', '#667eea', '2026-04-22 12:36:21'),
(3, 'sidebar_color', '#1e293b', '2026-04-22 12:36:21'),
(4, 'logo_path', '', '2026-04-22 12:36:21');

-- --------------------------------------------------------

--
-- Table structure for table `table_reservations`
--

CREATE TABLE `table_reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `guest_count` int(11) DEFAULT 2,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `points_used` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `table_reservations`
--

INSERT INTO `table_reservations` (`reservation_id`, `user_id`, `facility_id`, `reservation_date`, `reservation_time`, `guest_count`, `special_requests`, `status`, `points_used`, `created_at`) VALUES
(1, 5, 3, '2026-04-24', '14:00:00', 2, 'birthday party', 'pending', 0, '2026-04-24 15:31:25');

-- --------------------------------------------------------

--
-- Table structure for table `transportpasses`
--

CREATE TABLE `transportpasses` (
  `PassID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `RouteName` varchar(100) NOT NULL,
  `ValidUntil` date NOT NULL,
  `Status` enum('Active','Expired','Cancelled') DEFAULT 'Active',
  `IssuedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transportpasses`
--

INSERT INTO `transportpasses` (`PassID`, `UserID`, `RouteName`, `ValidUntil`, `Status`, `IssuedAt`) VALUES
(1, 2, 'Malabe - Colombo', '2026-04-22', 'Active', '2026-03-23 08:23:45'),
(2, 3, 'Malabe - Kandy', '2026-04-07', 'Active', '2026-03-23 08:23:45'),
(3, 5, 'Hendala', '2026-05-26', 'Active', '2026-04-26 07:09:50'),
(4, 6, 'Malabe', '2026-05-26', 'Active', '2026-04-26 16:57:09'),
(5, 5, 'Malabe', '2026-05-27', 'Active', '2026-04-27 08:23:14');

-- --------------------------------------------------------

--
-- Table structure for table `upcomingevents`
--

CREATE TABLE `upcomingevents` (
  `EventID` int(11) NOT NULL,
  `Title` varchar(200) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Location` varchar(200) DEFAULT NULL,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `Category` enum('SU','Club','Workshop') DEFAULT NULL,
  `DaysUntil` int(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upcomingevents`
--

INSERT INTO `upcomingevents` (`EventID`, `Title`, `Description`, `Location`, `StartTime`, `EndTime`, `Category`, `DaysUntil`) VALUES
(1, 'Tech Conference 2026', 'Annual tech summit with workshops', 'Colombo', '2026-10-05 09:00:00', '2026-10-07 18:00:00', '', 161),
(2, 'Art Expo', 'Modern & traditional art exhibition', 'Kandy', '2026-10-12 10:00:00', '2026-10-15 20:00:00', '', 168),
(3, 'Music Festival', 'Outdoor live music event', 'Galle', '2026-10-20 15:00:00', '2026-10-22 23:00:00', '', 176),
(4, 'Startup Meetup', 'Networking for entrepreneurs', 'Negombo', '2026-10-28 18:30:00', '2026-10-28 21:00:00', '', 184),
(5, 'Cyber Security Workshop', 'Hands-on security training', 'Online', '2026-11-02 09:00:00', '2026-11-04 17:00:00', 'Workshop', 189),
(6, 'Food Carnival', 'Street food & cultural performances', 'Jaffna', '2026-11-10 11:00:00', '2026-11-12 22:00:00', '', 197),
(7, 'AI Summit', 'Future of artificial intelligence', 'Colombo', '2026-11-18 08:30:00', '2026-11-19 17:30:00', '', 205);

-- --------------------------------------------------------

--
-- Table structure for table `usergroupmembers`
--

CREATE TABLE `usergroupmembers` (
  `GroupID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `AddedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usergroups`
--

CREATE TABLE `usergroups` (
  `GroupID` int(11) NOT NULL,
  `GroupName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usergroups`
--

INSERT INTO `usergroups` (`GroupID`, `GroupName`, `Description`, `CreatedBy`, `CreatedAt`) VALUES
(1, 'All Students', 'All registered students', NULL, '2026-03-23 08:23:45'),
(2, 'Active Users', 'Users who logged in within last 30 days', NULL, '2026-03-23 08:23:45'),
(3, 'Gym Members', 'Users who have checked into gym', NULL, '2026-03-23 08:23:45'),
(4, 'Library Users', 'Users who have borrowed books', NULL, '2026-03-23 08:23:45'),
(5, 'Transport Users', 'Users who use campus transport', NULL, '2026-03-23 08:23:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `StudentID` varchar(20) DEFAULT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('Admin','User','Visitor') DEFAULT 'User',
  `PointsBalance` int(11) DEFAULT 0,
  `MembershipStatus` enum('Active','Inactive','Expired') DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `StudentID`, `Name`, `Email`, `PasswordHash`, `Role`, `PointsBalance`, `MembershipStatus`, `CreatedAt`) VALUES
(2, 'STU002', 'John Doe', 'john.doe@university.lk', '$2y$10$YourHashedPasswordHere', 'User', 100, 'Active', '2026-03-23 08:23:44'),
(3, 'STU003', 'Jane Smith', 'jane.smith@university.lk', '$2y$10$YourHashedPasswordHere', 'User', 150, 'Active', '2026-03-23 08:23:44'),
(4, 'STU004', 'Visitor User', 'visitor@university.lk', '$2y$10$YourHashedPasswordHere', 'Visitor', 0, 'Active', '2026-03-23 08:23:44'),
(5, 'STU005', 'Prasadi Malsha Abeysekara', 'prasadimalshapma@gmail.com', '$2y$10$j2I16rHQ8Cb7yeTTd4Snt.Hm8LUxBwOSD60/TEzie75qI.ucXJm5e', 'User', 406, 'Active', '2026-03-24 06:01:30'),
(6, 'STU006', 'Kasun Gayantha', 'kasungayantha@gmail.com', '$2y$10$TfqcXKaG3u6TOW7j9bD8tOxXBPcO6BN.JuosBzk5hWIT5YqCkYczq', 'User', 170, 'Active', '2026-03-24 09:12:12'),
(7, 'ADMIN001', 'Admin User', 'admin@synergyhub.com', '$2y$10$Pn750zZFEyjj0QMQZKLy8eFaxalqKoFv5kM8glhkn2Wlp3/dSykke', 'Admin', 40, 'Active', '2026-04-26 18:38:09'),
(8, 'STU007', 'Manthusi Karunanayaka', 'manthusi@gmail.com', '$2y$10$5Rb9UC5ej5MhzfvMOdFCQORatl.ucvTHu37h8jPKLoFO8f1Qrufo6', 'User', 25, 'Active', '2026-04-27 07:59:25'),
(10, 'STU008', 'Amaya Adikari', 'amayaadikari@gmail.com', '$2y$10$Y3krDHfoCawmGaW1pdywq.foiaMYbVMj8U0f8Mvn9JSow.ojvF7rm', 'User', 0, 'Active', '2026-04-28 12:20:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activitylogs`
--
ALTER TABLE `activitylogs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `idx_activity_user` (`UserID`),
  ADD KEY `idx_activity_timestamp` (`Timestamp`);

--
-- Indexes for table `auditlogs`
--
ALTER TABLE `auditlogs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_timestamp` (`Timestamp`),
  ADD KEY `idx_action` (`Action`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_available` (`available`);

--
-- Indexes for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD PRIMARY KEY (`borrow_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due` (`due_date`);

--
-- Indexes for table `bus_routes`
--
ALTER TABLE `bus_routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `route_id` (`route_id`),
  ADD KEY `idx_route` (`route_id`);

--
-- Indexes for table `cafe_menu`
--
ALTER TABLE `cafe_menu`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `campus_transport`
--
ALTER TABLE `campus_transport`
  ADD PRIMARY KEY (`route_id`);

--
-- Indexes for table `checkins`
--
ALTER TABLE `checkins`
  ADD PRIMARY KEY (`CheckInID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_facility` (`FacilityID`),
  ADD KEY `idx_user_timestamp` (`UserID`,`Timestamp`),
  ADD KEY `idx_timestamp` (`Timestamp`);

--
-- Indexes for table `class_bookings`
--
ALTER TABLE `class_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_class` (`class_id`);

--
-- Indexes for table `clubmemberships`
--
ALTER TABLE `clubmemberships`
  ADD PRIMARY KEY (`MembershipID`),
  ADD UNIQUE KEY `unique_membership` (`ClubID`,`UserID`),
  ADD KEY `idx_club` (`ClubID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_role` (`Role`);

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`ClubID`),
  ADD UNIQUE KEY `Name` (`Name`),
  ADD KEY `idx_leader` (`LeaderID`),
  ADD KEY `idx_status` (`Status`);
ALTER TABLE `clubs` ADD FULLTEXT KEY `ft_club_description` (`Description`);

--
-- Indexes for table `emergencyalerts`
--
ALTER TABLE `emergencyalerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expires_at` (`expires_at`),
  ADD KEY `severity` (`severity`);

--
-- Indexes for table `emergencycontacts`
--
ALTER TABLE `emergencycontacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eventbookings`
--
ALTER TABLE `eventbookings`
  ADD PRIMARY KEY (`BookingID`),
  ADD UNIQUE KEY `TicketNumber` (`TicketNumber`),
  ADD KEY `idx_event` (`EventID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_ticket` (`TicketNumber`),
  ADD KEY `idx_status` (`Status`);

--
-- Indexes for table `eventlikes`
--
ALTER TABLE `eventlikes`
  ADD PRIMARY KEY (`LikeID`),
  ADD UNIQUE KEY `unique_like` (`EventID`,`UserID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `idx_event_likes` (`EventID`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`EventID`),
  ADD KEY `idx_starttime` (`StartTime`),
  ADD KEY `idx_category` (`Category`),
  ADD KEY `idx_organizer` (`OrganizerID`),
  ADD KEY `idx_event_dates` (`StartTime`,`EndTime`);
ALTER TABLE `events` ADD FULLTEXT KEY `ft_event_description` (`Description`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`FacilityID`),
  ADD KEY `idx_type` (`Type`),
  ADD KEY `idx_status` (`Status`);

--
-- Indexes for table `field_bookings`
--
ALTER TABLE `field_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`booking_date`);

--
-- Indexes for table `fitness_classes`
--
ALTER TABLE `fitness_classes`
  ADD PRIMARY KEY (`class_id`);

--
-- Indexes for table `gamefield`
--
ALTER TABLE `gamefield`
  ADD PRIMARY KEY (`GameID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_timestamp` (`Timestamp`),
  ADD KEY `idx_gametype` (`GameType`),
  ADD KEY `idx_user_games` (`UserID`,`Timestamp`);

--
-- Indexes for table `gps_checkin_history`
--
ALTER TABLE `gps_checkin_history`
  ADD PRIMARY KEY (`checkin_id`),
  ADD UNIQUE KEY `unique_daily_gps` (`user_id`,`facility_id`,`checkin_date`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `facility_id` (`facility_id`),
  ADD KEY `checkin_date` (`checkin_date`);

--
-- Indexes for table `gym_equipment`
--
ALTER TABLE `gym_equipment`
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `gym_status`
--
ALTER TABLE `gym_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `importhistory`
--
ALTER TABLE `importhistory`
  ADD PRIMARY KEY (`ImportID`),
  ADD KEY `ImportedBy` (`ImportedBy`);

--
-- Indexes for table `lifeguards`
--
ALTER TABLE `lifeguards`
  ADD PRIMARY KEY (`lifeguard_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lifeguard_schedule`
--
ALTER TABLE `lifeguard_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `unique_schedule` (`lifeguard_id`,`schedule_date`,`time_slot`),
  ADD KEY `idx_date` (`schedule_date`),
  ADD KEY `idx_lifeguard` (`lifeguard_id`);

--
-- Indexes for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_expiry` (`expiry_date`);

--
-- Indexes for table `membershiptiers`
--
ALTER TABLE `membershiptiers`
  ADD PRIMARY KEY (`TierID`);

--
-- Indexes for table `notificationlog`
--
ALTER TABLE `notificationlog`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `CreatedBy` (`CreatedBy`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_scheduled` (`ScheduledFor`),
  ADD KEY `idx_type_target` (`Type`,`TargetType`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `notificationtemplates`
--
ALTER TABLE `notificationtemplates`
  ADD PRIMARY KEY (`TemplateID`),
  ADD KEY `CreatedBy` (`CreatedBy`),
  ADD KEY `idx_type` (`Type`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_timestamp` (`Timestamp`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_user_orders` (`UserID`,`Timestamp`);

--
-- Indexes for table `pointsconfig`
--
ALTER TABLE `pointsconfig`
  ADD PRIMARY KEY (`ConfigID`),
  ADD UNIQUE KEY `ActionType` (`ActionType`);

--
-- Indexes for table `pointsexpiration`
--
ALTER TABLE `pointsexpiration`
  ADD PRIMARY KEY (`ExpirationID`);

--
-- Indexes for table `pointshistory`
--
ALTER TABLE `pointshistory`
  ADD PRIMARY KEY (`HistoryID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `pool_bookings`
--
ALTER TABLE `pool_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `unique_booking` (`facility_id`,`booking_date`,`lane_number`,`time_slot`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`booking_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `medical_report_id` (`medical_report_id`);

--
-- Indexes for table `qr_scan_history`
--
ALTER TABLE `qr_scan_history`
  ADD PRIMARY KEY (`scan_id`),
  ADD UNIQUE KEY `unique_daily_scan` (`user_id`,`qr_code`,`scan_date`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `scan_date` (`scan_date`),
  ADD KEY `qr_code` (`qr_code`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`RewardID`),
  ADD KEY `idx_points` (`PointsRequired`),
  ADD KEY `idx_availability` (`Availability`);

--
-- Indexes for table `rewardsredemption`
--
ALTER TABLE `rewardsredemption`
  ADD PRIMARY KEY (`RedemptionID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `RewardID` (`RewardID`);

--
-- Indexes for table `safetyincidents`
--
ALTER TABLE `safetyincidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_date` (`report_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `safetyquiz`
--
ALTER TABLE `safetyquiz`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `safetytips`
--
ALTER TABLE `safetytips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `priority` (`priority`);

--
-- Indexes for table `special_offers`
--
ALTER TABLE `special_offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `idx_facility` (`facility_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `streakbonuses`
--
ALTER TABLE `streakbonuses`
  ADD PRIMARY KEY (`StreakID`);

--
-- Indexes for table `study_rooms`
--
ALTER TABLE `study_rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `su_events`
--
ALTER TABLE `su_events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `table_reservations`
--
ALTER TABLE `table_reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `facility_id` (`facility_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`reservation_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `transportpasses`
--
ALTER TABLE `transportpasses`
  ADD PRIMARY KEY (`PassID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_validuntil` (`ValidUntil`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_user_valid` (`UserID`,`ValidUntil`);

--
-- Indexes for table `upcomingevents`
--
ALTER TABLE `upcomingevents`
  ADD PRIMARY KEY (`EventID`);

--
-- Indexes for table `usergroupmembers`
--
ALTER TABLE `usergroupmembers`
  ADD PRIMARY KEY (`GroupID`,`UserID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `usergroups`
--
ALTER TABLE `usergroups`
  ADD PRIMARY KEY (`GroupID`),
  ADD UNIQUE KEY `GroupName` (`GroupName`),
  ADD KEY `CreatedBy` (`CreatedBy`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_email` (`Email`),
  ADD KEY `idx_role` (`Role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activitylogs`
--
ALTER TABLE `activitylogs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `auditlogs`
--
ALTER TABLE `auditlogs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  MODIFY `borrow_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bus_routes`
--
ALTER TABLE `bus_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cafe_menu`
--
ALTER TABLE `cafe_menu`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `campus_transport`
--
ALTER TABLE `campus_transport`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `checkins`
--
ALTER TABLE `checkins`
  MODIFY `CheckInID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `class_bookings`
--
ALTER TABLE `class_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `clubmemberships`
--
ALTER TABLE `clubmemberships`
  MODIFY `MembershipID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `ClubID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `emergencyalerts`
--
ALTER TABLE `emergencyalerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `emergencycontacts`
--
ALTER TABLE `emergencycontacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `eventbookings`
--
ALTER TABLE `eventbookings`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eventlikes`
--
ALTER TABLE `eventlikes`
  MODIFY `LikeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `FacilityID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `field_bookings`
--
ALTER TABLE `field_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fitness_classes`
--
ALTER TABLE `fitness_classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gamefield`
--
ALTER TABLE `gamefield`
  MODIFY `GameID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gps_checkin_history`
--
ALTER TABLE `gps_checkin_history`
  MODIFY `checkin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gym_equipment`
--
ALTER TABLE `gym_equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gym_status`
--
ALTER TABLE `gym_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `importhistory`
--
ALTER TABLE `importhistory`
  MODIFY `ImportID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lifeguards`
--
ALTER TABLE `lifeguards`
  MODIFY `lifeguard_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lifeguard_schedule`
--
ALTER TABLE `lifeguard_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_reports`
--
ALTER TABLE `medical_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `membershiptiers`
--
ALTER TABLE `membershiptiers`
  MODIFY `TierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notificationlog`
--
ALTER TABLE `notificationlog`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notificationtemplates`
--
ALTER TABLE `notificationtemplates`
  MODIFY `TemplateID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `pointsconfig`
--
ALTER TABLE `pointsconfig`
  MODIFY `ConfigID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pointsexpiration`
--
ALTER TABLE `pointsexpiration`
  MODIFY `ExpirationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pointshistory`
--
ALTER TABLE `pointshistory`
  MODIFY `HistoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `pool_bookings`
--
ALTER TABLE `pool_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qr_scan_history`
--
ALTER TABLE `qr_scan_history`
  MODIFY `scan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `RewardID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rewardsredemption`
--
ALTER TABLE `rewardsredemption`
  MODIFY `RedemptionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `safetyincidents`
--
ALTER TABLE `safetyincidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `safetyquiz`
--
ALTER TABLE `safetyquiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `safetytips`
--
ALTER TABLE `safetytips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `special_offers`
--
ALTER TABLE `special_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `streakbonuses`
--
ALTER TABLE `streakbonuses`
  MODIFY `StreakID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `study_rooms`
--
ALTER TABLE `study_rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `su_events`
--
ALTER TABLE `su_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `table_reservations`
--
ALTER TABLE `table_reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transportpasses`
--
ALTER TABLE `transportpasses`
  MODIFY `PassID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `upcomingevents`
--
ALTER TABLE `upcomingevents`
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `usergroups`
--
ALTER TABLE `usergroups`
  MODIFY `GroupID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activitylogs`
--
ALTER TABLE `activitylogs`
  ADD CONSTRAINT `activitylogs_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `auditlogs`
--
ALTER TABLE `auditlogs`
  ADD CONSTRAINT `auditlogs_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD CONSTRAINT `borrowed_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrowed_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `checkins`
--
ALTER TABLE `checkins`
  ADD CONSTRAINT `checkins_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `checkins_ibfk_2` FOREIGN KEY (`FacilityID`) REFERENCES `facilities` (`FacilityID`) ON DELETE CASCADE;

--
-- Constraints for table `class_bookings`
--
ALTER TABLE `class_bookings`
  ADD CONSTRAINT `class_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_bookings_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `fitness_classes` (`class_id`) ON DELETE CASCADE;

--
-- Constraints for table `clubmemberships`
--
ALTER TABLE `clubmemberships`
  ADD CONSTRAINT `clubmemberships_ibfk_1` FOREIGN KEY (`ClubID`) REFERENCES `clubs` (`ClubID`) ON DELETE CASCADE,
  ADD CONSTRAINT `clubmemberships_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `clubs`
--
ALTER TABLE `clubs`
  ADD CONSTRAINT `clubs_ibfk_1` FOREIGN KEY (`LeaderID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `eventbookings`
--
ALTER TABLE `eventbookings`
  ADD CONSTRAINT `eventbookings_ibfk_1` FOREIGN KEY (`EventID`) REFERENCES `events` (`EventID`) ON DELETE CASCADE,
  ADD CONSTRAINT `eventbookings_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `eventlikes`
--
ALTER TABLE `eventlikes`
  ADD CONSTRAINT `eventlikes_ibfk_1` FOREIGN KEY (`EventID`) REFERENCES `events` (`EventID`) ON DELETE CASCADE,
  ADD CONSTRAINT `eventlikes_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`OrganizerID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `field_bookings`
--
ALTER TABLE `field_bookings`
  ADD CONSTRAINT `field_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `gamefield`
--
ALTER TABLE `gamefield`
  ADD CONSTRAINT `gamefield_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `importhistory`
--
ALTER TABLE `importhistory`
  ADD CONSTRAINT `importhistory_ibfk_1` FOREIGN KEY (`ImportedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `lifeguard_schedule`
--
ALTER TABLE `lifeguard_schedule`
  ADD CONSTRAINT `lifeguard_schedule_ibfk_1` FOREIGN KEY (`lifeguard_id`) REFERENCES `lifeguards` (`lifeguard_id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD CONSTRAINT `medical_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `notificationlog`
--
ALTER TABLE `notificationlog`
  ADD CONSTRAINT `notificationlog_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `notificationtemplates`
--
ALTER TABLE `notificationtemplates`
  ADD CONSTRAINT `notificationtemplates_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `pointshistory`
--
ALTER TABLE `pointshistory`
  ADD CONSTRAINT `pointshistory_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `pool_bookings`
--
ALTER TABLE `pool_bookings`
  ADD CONSTRAINT `pool_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `pool_bookings_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`FacilityID`) ON DELETE CASCADE,
  ADD CONSTRAINT `pool_bookings_ibfk_3` FOREIGN KEY (`medical_report_id`) REFERENCES `medical_reports` (`report_id`) ON DELETE SET NULL;

--
-- Constraints for table `rewardsredemption`
--
ALTER TABLE `rewardsredemption`
  ADD CONSTRAINT `rewardsredemption_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `rewardsredemption_ibfk_2` FOREIGN KEY (`RewardID`) REFERENCES `rewards` (`RewardID`) ON DELETE CASCADE;

--
-- Constraints for table `special_offers`
--
ALTER TABLE `special_offers`
  ADD CONSTRAINT `special_offers_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`FacilityID`) ON DELETE CASCADE;

--
-- Constraints for table `table_reservations`
--
ALTER TABLE `table_reservations`
  ADD CONSTRAINT `table_reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `table_reservations_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`FacilityID`) ON DELETE CASCADE;

--
-- Constraints for table `transportpasses`
--
ALTER TABLE `transportpasses`
  ADD CONSTRAINT `transportpasses_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `usergroupmembers`
--
ALTER TABLE `usergroupmembers`
  ADD CONSTRAINT `usergroupmembers_ibfk_1` FOREIGN KEY (`GroupID`) REFERENCES `usergroups` (`GroupID`) ON DELETE CASCADE,
  ADD CONSTRAINT `usergroupmembers_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `usergroups`
--
ALTER TABLE `usergroups`
  ADD CONSTRAINT `usergroups_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
