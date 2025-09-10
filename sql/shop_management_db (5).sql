-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2025 at 06:44 PM
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
-- Database: `shop_management_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Makwana Vishal', 'makwanavishal588@gmail.com', '$2y$10$4TsVutB69yx3guz.kYrRXek2XoFDSrHEvzZUsFJCw15crU9En5Zvi', '2025-07-28 12:08:28');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_mobile` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `shop_id`, `customer_name`, `customer_mobile`, `total_amount`, `created_at`, `payment_method`) VALUES
(116, 5, 'abc', '1122112222', 1100.00, '2025-07-06 07:12:26', 'cash'),
(117, 5, 'vishal', '1122112211', 990.00, '2025-07-06 07:17:50', 'cash'),
(118, 5, 'vishal', '1212121212', 1140.00, '2025-07-06 10:58:31', 'cash'),
(119, 5, 'abc', '1122112211', 2238.90, '2025-07-06 11:01:33', 'cash'),
(120, 5, 'xyzd', '1122112222', 5617.80, '2025-07-06 11:26:11', 'cash'),
(121, 5, 'abc', '9016092513', 1098.90, '2025-07-06 11:26:40', 'cash'),
(122, 5, 'vishal', '1122112211', 1098.90, '2025-07-06 11:28:35', 'cash'),
(123, 5, 'rohan', '0000011111', 3378.90, '2025-07-06 12:36:52', 'cash'),
(124, 5, 'xyzd', '1122112222', 2280.00, '2025-07-07 08:43:08', 'cash'),
(125, 5, 'vishal', '0000011111', 1098.90, '2025-07-07 08:45:20', 'cash'),
(126, 5, 'xyzd', '1212121212', 2197.80, '2025-07-07 09:21:02', 'cash'),
(127, 5, 'vishal', '1122112211', 2280.00, '2025-07-09 10:34:42', 'cash'),
(128, 5, 'vishal', '9016092513', 2280.00, '2025-07-09 10:36:30', 'cash'),
(161, 8, 'vikas', '9090909090', 585.00, '2025-07-21 11:03:39', 'cash'),
(162, 8, 'soham', '1234567890', 1140.00, '2025-07-21 11:20:05', 'cash'),
(163, 8, 'vinod', '9090909090', 1640.00, '2025-07-21 11:21:06', 'cash'),
(164, 8, 'sonam', '8899776655', 500.00, '2025-07-21 11:37:40', 'cash'),
(165, 8, 'soham', '1234567890', 485.00, '2025-07-21 11:51:03', 'cash'),
(166, 8, 'soham', '8899776655', 29100.00, '2025-07-21 12:33:51', 'cash'),
(167, 8, 'vikas', '1234567891', 485.00, '2025-07-21 12:37:26', 'cash'),
(168, 8, 'vikas', '1234567890', 490.00, '2025-07-21 12:37:58', 'cash'),
(169, 8, 'xyz', '9090909090', 3000.00, '2025-07-21 12:42:05', 'cash'),
(170, 9, 'rohan', '9090909090', 100.00, '2025-07-21 13:11:56', 'cash'),
(171, 9, 'soham', '8899776655', 1345.50, '2025-07-21 13:21:17', 'cash'),
(172, 9, 'vikas', '1234567890', 299.00, '2025-07-21 13:22:14', 'cash'),
(177, 12, 'vikas', '1234567890', 948.10, '2025-07-24 12:51:36', 'cash'),
(178, 12, 'hkl', '8899776655', 749.00, '2025-07-24 12:55:10', 'cash'),
(179, 12, 'soham', '1234567890', 2398.00, '2025-07-24 12:56:13', 'cash'),
(180, 12, 'vishal', '1234567890', 1698.00, '2025-07-24 12:58:16', 'cash'),
(181, 12, 'parth', '8899776655', 1199.00, '2025-07-24 13:01:23', 'cash'),
(183, 8, 'vikas', '1234567890', 123534.00, '2025-07-25 12:38:59', 'cash'),
(205, 12, 'vikas', '8899776655', 749.00, '2025-08-19 13:36:41', 'upi'),
(206, 12, 'vishal', '1234567890', 3000.00, '2025-08-19 13:48:57', 'card'),
(207, 12, 'vikas', '1234567890', 4750.00, '2025-08-19 13:50:46', 'upi'),
(208, 12, 'soham', '9016092513', 1518.10, '2025-08-19 14:00:02', 'cash'),
(212, 10, 'soham', '1234567891', 1440.00, '2025-08-22 12:42:31', 'upi'),
(213, 10, 'vishal', '9016092513', 1512.00, '2025-08-22 12:43:26', 'online'),
(214, 12, 'soham', '9090909090', 2450.00, '2025-08-22 13:25:58', 'card'),
(215, 10, 'vishal', '9090909090', 120.00, '2025-08-23 05:48:06', 'cash'),
(216, 10, 'vishal', '1234567890', 1000.00, '2025-08-23 05:58:27', 'card'),
(217, 12, 'xyz', '1234567890', 749.00, '2025-08-23 12:38:31', 'cash'),
(220, 12, 'vishal', '1234567890', 55463.85, '2025-08-24 05:22:16', 'cash'),
(222, 15, 'soham', '1234567890', 720.00, '2025-08-25 12:46:27', 'cash'),
(223, 15, 'soham', '1234567890', 100.00, '2025-08-25 12:48:46', 'cash'),
(224, 15, 'vishal', '1234567890', 89.00, '2025-08-25 12:58:03', 'cash'),
(225, 15, 'vishal', '9090909090', 100.00, '2025-08-25 13:10:38', 'card'),
(226, 15, 'vikas', '9090909090', 89.00, '2025-08-25 13:16:04', 'upi'),
(228, 15, 'vishal', '1234567890', 89.00, '2025-08-25 13:39:42', 'cash'),
(231, 15, 'vishal', '9090909090', 178.00, '2025-08-25 13:57:53', 'card'),
(232, 15, 'xyz', '1234567891', 100.00, '2025-08-25 13:58:57', 'upi'),
(233, 15, 'vikas', '1234567891', 300.00, '2025-08-25 14:16:18', 'cash'),
(234, 15, 'mohan', '1234567890', 100.00, '2025-08-25 14:21:06', 'card'),
(235, 15, 'soham', '1234567890', 100.00, '2025-08-25 14:22:29', 'upi'),
(236, 15, 'soham', '1234567890', 100.00, '2025-08-25 14:30:20', 'upi'),
(237, 12, 'vishal', '9090909090', 499.00, '2025-08-25 16:00:51', 'upi');

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `product_cost_at_sale` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(5,2) DEFAULT 0.00,
  `product_name_at_sale` varchar(255) NOT NULL,
  `product_price_at_sale` decimal(10,2) NOT NULL,
  `product_discount_percent_at_sale` decimal(5,2) NOT NULL DEFAULT 0.00,
  `product_is_decimal_quantity_at_sale` tinyint(1) NOT NULL DEFAULT 0,
  `product_unit_measurement_at_sale` varchar(50) NOT NULL DEFAULT 'Units',
  `product_attributes_at_sale` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`id`, `bill_id`, `product_id`, `product_name`, `price`, `quantity`, `total`, `product_cost_at_sale`, `discount`, `product_name_at_sale`, `product_price_at_sale`, `product_discount_percent_at_sale`, `product_is_decimal_quantity_at_sale`, `product_unit_measurement_at_sale`, `product_attributes_at_sale`) VALUES
(118, 116, 'PRD001', '', 1100.00, 1.00, 1100.00, 0.00, 0.00, '', 0.00, 0.00, 0, 'Units', NULL),
(119, 117, 'PRD001', 'HP Laptop', 990.00, 1.00, 990.00, 0.00, 0.00, '', 0.00, 0.00, 0, 'Units', NULL),
(120, 118, 'PRD001', '', 1140.00, 1.00, 1140.00, 0.00, 5.00, '', 0.00, 0.00, 0, 'Units', NULL),
(121, 119, 'PRD001', '', 1140.00, 1.00, 1140.00, 0.00, 5.00, '', 0.00, 0.00, 0, 'Units', NULL),
(122, 119, 'PRD002', '', 1098.90, 1.00, 1098.90, 0.00, 1.00, '', 0.00, 0.00, 0, 'Units', NULL),
(123, 120, 'PRD001', '', 1140.00, 3.00, 3420.00, 0.00, 5.00, '', 0.00, 0.00, 0, 'Units', NULL),
(124, 120, 'PRD002', '', 1098.90, 2.00, 2197.80, 0.00, 1.00, '', 0.00, 0.00, 0, 'Units', NULL),
(125, 121, 'PRD002', '', 1098.90, 1.00, 1098.90, 0.00, 1.00, '', 0.00, 0.00, 0, 'Units', NULL),
(126, 122, 'PRD002', '', 1098.90, 1.00, 1098.90, 0.00, 1.00, '', 0.00, 0.00, 0, 'Units', NULL),
(127, 123, 'PRD001', '', 1140.00, 2.00, 2280.00, 0.00, 5.00, '', 0.00, 0.00, 0, 'Units', NULL),
(128, 123, 'PRD002', '', 1098.90, 1.00, 1098.90, 0.00, 1.00, '', 0.00, 0.00, 0, 'Units', NULL),
(129, 124, 'PRD001', 'Laptop-2', 1140.00, 2.00, 2280.00, 0.00, 0.00, '', 0.00, 0.00, 0, 'Units', NULL),
(130, 125, 'PRD002', '', 1098.90, 1.00, 1098.90, 0.00, 1.00, '', 0.00, 0.00, 0, 'Units', NULL),
(131, 126, 'PRD002', '', 1098.90, 2.00, 2197.80, 0.00, 1.00, '', 0.00, 0.00, 0, 'Units', NULL),
(132, 127, 'PRD001', '', 1140.00, 2.00, 2280.00, 0.00, 5.00, '', 0.00, 0.00, 0, 'Units', NULL),
(133, 128, 'PRD001', 'Laptop-2', 1140.00, 2.00, 2280.00, 0.00, 0.00, '', 0.00, 0.00, 0, 'Units', NULL),
(171, 161, 'PRD010', 'Black Jeans', 585.00, 1.00, 585.00, 0.00, 0.00, 'Black Jeans', 600.00, 2.50, 0, 'Units', NULL),
(172, 162, 'PRD010', 'Black Jeans', 570.00, 2.00, 1140.00, 0.00, 0.00, 'Black Jeans', 600.00, 5.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Black\"},{\"name\":\"Size\",\"value\":\"30\"}]'),
(173, 163, 'PRD010', 'Black Jeans', 570.00, 2.00, 1140.00, 0.00, 0.00, 'Black Jeans', 600.00, 5.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Black\"},{\"name\":\"Size\",\"value\":\"30\"}]'),
(174, 163, 'PRD004', 'Pink T-Shirt', 500.00, 1.00, 500.00, 0.00, 0.00, 'Pink T-Shirt', 500.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Red\"},{\"name\":\"Material\",\"value\":\"Cotton\"},{\"name\":\"Size\",\"value\":\"L\"}]'),
(175, 164, 'PRD009', 'RED SHIRT', 500.00, 1.00, 500.00, 0.00, 0.00, 'RED SHIRT', 500.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Red\"},{\"name\":\"Size\",\"value\":\"M\"}]'),
(176, 165, 'PRD009', 'RED SHIRT', 485.00, 1.00, 485.00, 0.00, 0.00, 'RED SHIRT', 500.00, 3.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Red\"},{\"name\":\"Size\",\"value\":\"M\"}]'),
(177, 166, 'PRD011', 'Sky Blue Jeans', 2910.00, 10.00, 29100.00, 0.00, 3.00, 'Sky Blue Jeans', 3000.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Sky Blue\"}]'),
(178, 167, 'PRD009', 'RED SHIRT', 485.00, 1.00, 485.00, 0.00, 3.00, 'RED SHIRT', 500.00, 3.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Red\"},{\"name\":\"Size\",\"value\":\"M\"}]'),
(179, 168, 'PRD009', 'RED SHIRT', 490.00, 1.00, 490.00, 0.00, 2.00, 'RED SHIRT', 500.00, 2.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Red\"},{\"name\":\"Size\",\"value\":\"M\"}]'),
(180, 169, 'PRD011', 'Sky Blue Jeans', 3000.00, 1.00, 3000.00, 0.00, 0.00, 'Sky Blue Jeans', 3000.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Sky Blue\"},{\"name\":\"Size\",\"value\":\"30\"}]'),
(181, 170, 'PRD001', 'Grand Mother Story Book', 100.00, 1.00, 100.00, 0.00, 0.00, 'Grand Mother Story Book', 100.00, 0.00, 0, 'Piece', '[]'),
(182, 171, 'PRD002', 'The Alchemist', 269.10, 5.00, 1345.50, 0.00, 10.00, 'The Alchemist', 299.00, 0.00, 0, 'Piece', '[{\"name\":\"Author\",\"value\":\"Paulo Coelho\"},{\"name\":\"Language\",\"value\":\"English\"},{\"name\":\"Type\",\"value\":\"Paperback\"}]'),
(183, 172, 'PRD002', 'The Alchemist', 299.00, 1.00, 299.00, 0.00, 0.00, 'The Alchemist', 299.00, 0.00, 0, 'Piece', '[{\"name\":\"Author\",\"value\":\"Paulo Coelho\"},{\"name\":\"Language\",\"value\":\"English\"},{\"name\":\"Type\",\"value\":\"Paperback\"}]'),
(192, 177, 'FASH001', 'Men’s Cotton T-Shirt', 474.05, 2.00, 948.10, 0.00, 5.00, 'Men’s Cotton T-Shirt', 499.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Navy Blue\"},{\"name\":\"Fabric\",\"value\":\"Cotton\"},{\"name\":\"Size\",\"value\":\"M\"}]'),
(193, 178, 'FASH002', 'Rayon Printed Kurti', 749.00, 1.00, 749.00, 0.00, 0.00, 'Rayon Printed Kurti', 749.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Green\"},{\"name\":\"Fabric\",\"value\":\"Rayon\"},{\"name\":\"Size\",\"value\":\"L\"}]'),
(194, 179, 'FASH003', 'Slim Fit Stretchable Jeans', 1199.00, 2.00, 2398.00, 0.00, 0.00, 'Slim Fit Stretchable Jeans', 1199.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Blue\"},{\"name\":\"Length\",\"value\":\"40\"},{\"name\":\"Waist\",\"value\":\"32\"}]'),
(195, 180, 'FASH001', 'Men’s Cotton T-Shirt', 499.00, 1.00, 499.00, 0.00, 0.00, 'Men’s Cotton T-Shirt', 499.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Navy Blue\"},{\"name\":\"Fabric\",\"value\":\"Cotton\"},{\"name\":\"Size\",\"value\":\"M\"}]'),
(196, 180, 'FASH003', 'Slim Fit Stretchable Jeans', 1199.00, 1.00, 1199.00, 0.00, 0.00, 'Slim Fit Stretchable Jeans', 1199.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Blue\"},{\"name\":\"Length\",\"value\":\"40\"},{\"name\":\"Waist\",\"value\":\"32\"}]'),
(197, 181, 'FASH003', 'Slim Fit Stretchable Jeans', 1199.00, 1.00, 1199.00, 0.00, 0.00, 'Slim Fit Stretchable Jeans', 1199.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Blue\"},{\"name\":\"Length\",\"value\":\"40\"},{\"name\":\"Waist\",\"value\":\"32\"}]'),
(199, 183, 'PRD003', 'Cotton Polo T-Shirt', 699.00, 56.00, 39144.00, 0.00, 0.00, 'Cotton Polo T-Shirt', 699.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Black\"},{\"name\":\"Size\",\"value\":\"M\"}]'),
(200, 183, 'PRD011', 'Sky Blue Jeans', 2910.00, 29.00, 84390.00, 0.00, 3.00, 'Sky Blue Jeans', 3000.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Sky Blue\"},{\"name\":\"Size\",\"value\":\"30\"}]'),
(230, 205, 'FASH002', 'Rayon Printed Kurti', 749.00, 1.00, 749.00, 0.00, 0.00, 'Rayon Printed Kurti', 749.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Green\"},{\"name\":\"Fabric\",\"value\":\"Rayon\"},{\"name\":\"Size\",\"value\":\"L\"}]'),
(231, 206, 'FASH004', 'Levi\\\'s Men\\\'s 511 Slim Fit Jeans', 1000.00, 3.00, 3000.00, 0.00, 0.00, 'Levi\\\'s Men\\\'s 511 Slim Fit Jeans', 1000.00, 0.00, 0, 'Piece', '[{\"name\":\"color\",\"value\":\"blue\"},{\"name\":\"length\",\"value\":\"34\"},{\"name\":\"Size\",\"value\":\"30\"}]'),
(232, 207, 'FASH004', 'Levi\\\'s Men\\\'s 511 Slim Fit Jeans', 950.00, 5.00, 4750.00, 0.00, 5.00, 'Levi\\\'s Men\\\'s 511 Slim Fit Jeans', 1000.00, 0.00, 0, 'Piece', '[{\"name\":\"color\",\"value\":\"blue\"},{\"name\":\"length\",\"value\":\"34\"},{\"name\":\"Size\",\"value\":\"30\"}]'),
(233, 208, 'FASH005', 'Utsa Indigo Foliage Pattern Kurti', 759.05, 2.00, 1518.10, 0.00, 5.00, 'Utsa Indigo Foliage Pattern Kurti', 799.00, 0.00, 0, 'Piece', '[{\"name\":\"Fabric\",\"value\":\"Polyster\"},{\"name\":\"Size\",\"value\":\"L\"}]'),
(237, 212, 'PROD102', 'Toor Dal', 144.00, 10.00, 1440.00, 0.00, 4.00, 'Toor Dal', 150.00, 0.00, 1, 'Kg', '[{\"name\":\"Type\",\"value\":\"Organic\"}]'),
(238, 213, 'PROD102', 'Toor Dal', 144.00, 10.50, 1512.00, 0.00, 4.00, 'Toor Dal', 150.00, 0.00, 1, 'Kg', '[{\"name\":\"Type\",\"value\":\"Organic\"}]'),
(239, 214, 'FASH006', '3bros Men\\\'s Solid Polo Neck T-shirt', 490.00, 5.00, 2450.00, 0.00, 2.00, '3bros Men\\\'s Solid Polo Neck T-shirt', 500.00, 0.00, 0, 'Piece', '[{\"name\":\"Brand\",\"value\":\"3BROS\"},{\"name\":\"Color\",\"value\":\"Pink\"},{\"name\":\"Fabric\",\"value\":\"Cotton Blend\"},{\"name\":\"Size\",\"value\":\"M\"},{\"name\":\"Sleeve\",\"value\":\"Short Sleeve\"},{\"name\":\"Type\",\"value\":\"Polo Neck\"}]'),
(240, 215, 'PROD101', 'Basmati Rice', 120.00, 1.00, 120.00, 0.00, 0.00, 'Basmati Rice', 120.00, 0.00, 1, 'Kg', '[{\"name\":\"Brand\",\"value\":\"Gold Rice\"}]'),
(241, 216, 'PROD105', 'Ghee', 1000.00, 1.00, 1000.00, 0.00, 0.00, 'Ghee', 1000.00, 0.00, 1, 'Kg', '[]'),
(242, 217, 'FASH002', 'Rayon Printed Kurti', 749.00, 1.00, 749.00, 0.00, 0.00, 'Rayon Printed Kurti', 749.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Green\"},{\"name\":\"Fabric\",\"value\":\"Rayon\"},{\"name\":\"Size\",\"value\":\"L\"}]'),
(245, 220, 'FASH001', 'Men’s Cotton T-Shirt', 474.05, 117.00, 55463.85, 0.00, 5.00, 'Men’s Cotton T-Shirt', 499.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Navy Blue\"},{\"name\":\"Fabric\",\"value\":\"Cotton\"},{\"name\":\"Size\",\"value\":\"M\"}]'),
(246, 222, 'PRD3', 'ANKSDS', 100.00, 1.00, 100.00, 0.00, 0.00, 'ANKSDS', 100.00, 0.00, 0, 'Set', '[]'),
(247, 222, 'PRD1', 'Basmati Rice 5kg', 100.00, 2.00, 200.00, 0.00, 0.00, 'Basmati Rice 5kg', 100.00, 0.00, 0, 'Piece', '[]'),
(248, 222, 'PRD5', 'Milk (1 Litre)', 110.00, 2.00, 220.00, 0.00, 0.00, 'Milk (1 Litre)', 110.00, 0.00, 0, 'Meter', '[]'),
(249, 222, 'PRD4', 'Canned Tomatoes', 100.00, 1.00, 100.00, 0.00, 0.00, 'Canned Tomatoes', 100.00, 0.00, 0, 'Box', '[]'),
(250, 222, 'PRD2', 'Fresh Apples (1kg)', 100.00, 1.00, 100.00, 0.00, 0.00, 'Fresh Apples (1kg)', 100.00, 0.00, 0, 'Kg', '[]'),
(251, 223, 'PRD1', 'Basmati Rice 5kg', 100.00, 1.00, 100.00, 0.00, 0.00, 'Basmati Rice 5kg', 100.00, 0.00, 0, 'Piece', '[]'),
(252, 224, 'PRD1', 'Basmati Rice 5kg', 89.00, 1.00, 89.00, 0.00, 0.00, 'Basmati Rice 5kg', 89.00, 0.00, 0, 'Piece', '[]'),
(253, 225, 'PRD5', 'Milk (1 Litre)', 100.00, 1.00, 100.00, 0.00, 0.00, 'Milk (1 Litre)', 100.00, 0.00, 0, 'Meter', '[]'),
(254, 226, 'PRD1', 'Basmati Rice 5kg', 89.00, 1.00, 89.00, 0.00, 0.00, 'Basmati Rice 5kg', 89.00, 0.00, 0, 'Piece', '[]'),
(255, 228, 'PRD1', 'Basmati Rice 5kg', 89.00, 1.00, 89.00, 0.00, 0.00, 'Basmati Rice 5kg', 89.00, 0.00, 0, 'Piece', '[]'),
(256, 231, 'PRD1', 'Basmati Rice 5kg', 89.00, 2.00, 178.00, 0.00, 0.00, 'Basmati Rice 5kg', 89.00, 0.00, 0, 'Piece', '[]'),
(257, 232, 'PRD5', 'Milk (1 Litre)', 100.00, 1.00, 100.00, 88.00, 0.00, 'Milk (1 Litre)', 100.00, 0.00, 0, 'Meter', '[]'),
(258, 233, 'PRD5', 'Milk (1 Litre)', 100.00, 3.00, 300.00, 30.00, 0.00, 'Milk (1 Litre)', 100.00, 0.00, 0, 'Meter', '[]'),
(259, 234, 'PRD5', 'Milk (1 Litre)', 100.00, 1.00, 100.00, 30.00, 0.00, 'Milk (1 Litre)', 100.00, 0.00, 0, 'Meter', '[]'),
(260, 235, 'PRD5', 'Milk (1 Litre)', 100.00, 1.00, 100.00, 50.00, 0.00, 'Milk (1 Litre)', 100.00, 0.00, 0, 'Meter', '[]'),
(261, 236, 'PRD5', 'Milk (1 Litre)', 100.00, 1.00, 100.00, 100.00, 0.00, 'Milk (1 Litre)', 100.00, 0.00, 0, 'Meter', '[]'),
(262, 237, 'FASH001', 'Men’s Cotton T-Shirt', 499.00, 1.00, 499.00, 350.00, 0.00, 'Men’s Cotton T-Shirt', 499.00, 0.00, 0, 'Piece', '[{\"name\":\"Color\",\"value\":\"Navy Blue\"},{\"name\":\"Fabric\",\"value\":\"Cotton\"},{\"name\":\"Size\",\"value\":\"M\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `shop_id`, `name`) VALUES
(13, 5, 'Laptop'),
(14, 6, 'Electronics'),
(18, 8, 'Hoodies'),
(16, 8, 'Jeans'),
(15, 8, 'Shirts'),
(17, 8, 'T-Shirts'),
(19, 8, 'Trousers'),
(22, 9, 'English'),
(20, 9, 'Geography'),
(21, 9, 'Social Science'),
(23, 9, 'Story'),
(24, 10, 'Grocery'),
(28, 12, 'Accessories'),
(27, 12, 'Jeans'),
(26, 12, 'Kurtis'),
(31, 12, 'RED T-SHIRT'),
(25, 12, 'T-SHIRT'),
(32, 14, 'Grocery'),
(37, 15, 'Hello'),
(34, 15, 'Mobile'),
(35, 15, 'Smart Phone'),
(33, 15, 'TV');

-- --------------------------------------------------------

--
-- Table structure for table `contact_requests`
--

CREATE TABLE `contact_requests` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `owner_name` varchar(255) NOT NULL,
  `owner_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_requests`
--

INSERT INTO `contact_requests` (`id`, `shop_id`, `owner_name`, `owner_email`, `subject`, `message`, `created_at`) VALUES
(2, 5, 'vishal makwana', 'admin@email.com', 'I Need Customize Bill Formate', 'Hello Dear Developers I Need To Customize Bill Formate With My Shop Related', '2025-07-06 11:51:21'),
(3, 10, 'mohan', 'mohan@email.com', 'Feedback Thank You', 'This Is Very Usefull Software', '2025-07-28 13:20:44'),
(4, 10, 'mohan', 'mohan@email.com', 'Feedback Thank You', 'Hello Dear Developer Can You Add Fetures For Pending Bills', '2025-07-28 13:21:38'),
(5, 10, 'mohan', 'mohan@email.com', 'Feedback Thank You', 'hello', '2025-07-28 13:41:25'),
(6, 10, 'mohan', 'mohan@email.com', 'Feedback Thank You', 'hii', '2025-07-28 13:43:40'),
(7, 10, 'mohan', 'mohan@email.com', 'hello', 'hello', '2025-08-14 15:03:46'),
(8, 10, 'mohan', 'mohan@email.com', 'hello', 'hello', '2025-08-14 15:03:59');

-- --------------------------------------------------------

--
-- Table structure for table `pending_bills`
--

CREATE TABLE `pending_bills` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_mobile` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bill_data_json` longtext NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_bills`
--

INSERT INTO `pending_bills` (`id`, `shop_id`, `customer_name`, `customer_mobile`, `created_at`, `updated_at`, `bill_data_json`, `notes`, `status`, `payment_method`) VALUES
(1, 10, 'vishal', '1234567890', '2025-07-26 17:15:19', '2025-07-26 17:16:37', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1,\"total\":120,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'loaded', 'cash'),
(2, 10, 'mohan', '1234567890', '2025-07-26 17:16:22', '2025-07-26 17:17:43', '[{\"product_id_string\":\"PROD105\",\"name\":\"Ghee\",\"base_price_at_add\":1000,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":0,\"qty_discount_percentage_at_add\":0,\"applied_discount_percent\":0,\"final_price_at_add\":1000,\"quantity\":1,\"total\":1000,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[]\"}]', NULL, 'completed', 'cash'),
(3, 10, 'vikas', '1234567890', '2025-07-26 17:19:02', '2025-07-26 17:20:25', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1,\"total\":120,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'loaded', 'cash'),
(4, 10, 'vishal', '8899776655', '2025-07-26 17:24:51', '2025-07-26 17:25:54', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1.25,\"total\":150,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'completed', 'cash'),
(5, 10, 'mohan', '9016092513', '2025-07-26 17:30:10', '2025-07-26 17:30:21', '[{\"product_id_string\":\"PROD102\",\"name\":\"Toor Dal\",\"base_price_at_add\":150,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":4,\"qty_discount_percentage_at_add\":4,\"applied_discount_percent\":0,\"final_price_at_add\":150,\"quantity\":1,\"total\":150,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Type\\\",\\\"value\\\":\\\"Organic\\\"}]\"}]', NULL, 'loaded', 'cash'),
(6, 10, 'vishal', '1234567890', '2025-07-28 19:00:14', '2025-07-28 19:07:33', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1,\"total\":120,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'loaded', 'cash'),
(7, 10, 'vikas', '1234567891', '2025-08-06 19:13:07', '2025-08-06 19:13:19', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1,\"total\":120,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'completed', 'cash'),
(8, 10, 'xyz', '1234567890', '2025-08-17 16:16:45', '2025-08-17 16:16:49', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1,\"total\":120,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'loaded', 'cash'),
(9, 10, 'vishal', '9090909090', '2025-08-18 19:42:08', '2025-08-18 19:51:28', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1,\"total\":120,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'completed', 'cash'),
(10, 10, 'vishal', '9090909090', '2025-08-18 19:50:58', '2025-08-18 19:53:15', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1,\"total\":120,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'completed', 'cash'),
(11, 10, 'soham', '1234567891', '2025-08-18 19:52:27', '2025-08-22 18:12:31', '[{\"product_id_string\":\"PROD102\",\"name\":\"Toor Dal\",\"base_price_at_add\":150,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":4,\"qty_discount_percentage_at_add\":4,\"applied_discount_percent\":4,\"final_price_at_add\":144,\"quantity\":10,\"total\":1440,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Type\\\",\\\"value\\\":\\\"Organic\\\"}]\"}]', NULL, 'completed', 'cash'),
(12, 12, 'vikas', '1234567890', '2025-08-19 19:20:34', '2025-08-19 19:20:46', '[{\"product_id_string\":\"FASH004\",\"name\":\"Levi\\\\\'s Men\\\\\'s 511 Slim Fit Jeans\",\"base_price_at_add\":1000,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":5,\"final_price_at_add\":950,\"quantity\":5,\"total\":4750,\"is_decimal\":0,\"unit_measurement_at_add\":\"Piece\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"color\\\",\\\"value\\\":\\\"blue\\\"},{\\\"name\\\":\\\"length\\\",\\\"value\\\":\\\"34\\\"},{\\\"name\\\":\\\"Size\\\",\\\"value\\\":\\\"30\\\"}]\"}]', NULL, 'completed', 'card'),
(13, 10, 'vishal', '9090909090', '2025-08-23 11:17:57', '2025-08-23 11:18:06', '[{\"product_id_string\":\"PROD101\",\"name\":\"Basmati Rice\",\"base_price_at_add\":120,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":5,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":120,\"quantity\":1,\"total\":120,\"is_decimal\":1,\"unit_measurement_at_add\":\"Kg\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Brand\\\",\\\"value\\\":\\\"Gold Rice\\\"}]\"}]', NULL, 'completed', 'card'),
(14, 12, '', '', '2025-08-23 18:06:57', '2025-08-23 18:07:10', '[{\"product_id_string\":\"FASH001\",\"name\":\"Men\\u2019s Cotton T-Shirt\",\"base_price_at_add\":499,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":2,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":499,\"quantity\":1,\"total\":499,\"is_decimal\":0,\"unit_measurement_at_add\":\"Piece\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Color\\\",\\\"value\\\":\\\"Navy Blue\\\"},{\\\"name\\\":\\\"Fabric\\\",\\\"value\\\":\\\"Cotton\\\"},{\\\"name\\\":\\\"Size\\\",\\\"value\\\":\\\"M\\\"}]\"}]', NULL, 'loaded', 'cash'),
(15, 12, 'soham', '1234567890', '2025-08-23 18:14:20', '2025-08-23 18:14:26', '[{\"product_id_string\":\"FASH001\",\"name\":\"Men\\u2019s Cotton T-Shirt\",\"base_price_at_add\":499,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":2,\"qty_discount_percentage_at_add\":5,\"applied_discount_percent\":0,\"final_price_at_add\":499,\"quantity\":1,\"total\":499,\"is_decimal\":0,\"unit_measurement_at_add\":\"Piece\",\"attributes_at_add_json\":\"[{\\\"name\\\":\\\"Color\\\",\\\"value\\\":\\\"Navy Blue\\\"},{\\\"name\\\":\\\"Fabric\\\",\\\"value\\\":\\\"Cotton\\\"},{\\\"name\\\":\\\"Size\\\",\\\"value\\\":\\\"M\\\"}]\"}]', NULL, 'loaded', 'cash'),
(16, 14, 'vishal', '1234567890', '2025-08-24 09:33:29', '2025-08-24 09:34:03', '[{\"product_id_string\":\"PROD5\",\"name\":\"Milk (1 Litre)\",\"base_price_at_add\":60,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":0,\"qty_discount_percentage_at_add\":0,\"applied_discount_percent\":0,\"final_price_at_add\":60,\"quantity\":1,\"total\":60,\"is_decimal\":1,\"unit_measurement_at_add\":\"0\",\"attributes_at_add_json\":\"[]\"}]', NULL, 'completed', 'cash'),
(17, 15, 'soham', '1234567890', '2025-08-25 20:00:08', '2025-08-25 20:00:20', '[{\"product_id_string\":\"PRD5\",\"name\":\"Milk (1 Litre)\",\"base_price_at_add\":100,\"cost_price_at_add\":100,\"discount_percent_at_add\":0,\"min_qty_for_discount_at_add\":0,\"qty_discount_percentage_at_add\":0,\"applied_discount_percent\":0,\"final_price_at_add\":100,\"quantity\":1,\"total\":100,\"is_decimal\":0,\"unit_measurement_at_add\":\"Meter\",\"attributes_at_add_json\":\"[]\"}]', NULL, 'completed', 'cash');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `stock_quantity` decimal(10,2) NOT NULL,
  `unit_measurement` varchar(50) NOT NULL,
  `min_qty_for_discount` int(11) DEFAULT 0,
  `qty_discount_percentage` decimal(5,2) DEFAULT 0.00,
  `image_path` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_decimal_quantity` tinyint(1) NOT NULL DEFAULT 0,
  `low_stock_threshold` decimal(10,2) DEFAULT 0.00,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `shop_id`, `product_id`, `name`, `description`, `price`, `cost_price`, `discount_percent`, `stock_quantity`, `unit_measurement`, `min_qty_for_discount`, `qty_discount_percentage`, `image_path`, `category_id`, `created_at`, `updated_at`, `is_decimal_quantity`, `low_stock_threshold`, `deleted_at`) VALUES
(12, 5, 'PRD001', 'Laptop-2', 'HP', 1200.00, NULL, 5.00, 87.00, '', 0, 0.00, 'uploads/prod_686a569d3bc8e.png', 13, '2025-07-06 10:57:33', '2025-07-09 10:36:30', 0, 0.00, NULL),
(13, 5, 'PRD002', 'VisionFlow', 'Dell', 1110.00, NULL, 1.00, 81.00, '', 0, 0.00, 'uploads/prod_686a57747ba27.png', 13, '2025-07-06 11:01:08', '2025-07-07 09:21:02', 0, 0.00, NULL),
(16, 6, 'PRD001', 'Oil', 'oil', 100.00, NULL, 10.00, 70.20, 'Liter', 0, 0.00, '', 14, '2025-07-14 17:38:55', '2025-07-16 17:37:54', 1, 0.00, NULL),
(17, 6, 'PRD002', 'RED T-SHIRT', 'RED T-SHIRT', 1000.00, NULL, 0.00, 2.00, 'Piece', 0, 0.00, '', 14, '2025-07-16 13:34:24', '2025-07-16 17:42:41', 0, 0.00, NULL),
(18, 6, 'PRD003', 'VisionFlow', 'TV', 1000.00, NULL, 2.00, 45.00, 'Piece', 0, 0.00, '', 14, '2025-07-16 14:44:54', '2025-07-16 14:44:54', 0, 0.00, NULL),
(19, 6, 'PRD004', 'Mixer', 'Mixer', 190.00, NULL, 1.00, 38.00, 'Piece', 0, 0.00, '', 14, '2025-07-16 14:45:46', '2025-07-16 15:13:20', 0, 0.00, NULL),
(20, 6, 'PRD005', 'RED T-SHIRT', 'red', 1000.00, 700.00, 0.00, 91.00, 'Piece', 0, 0.00, '', 14, '2025-07-16 15:10:24', '2025-07-16 17:37:54', 0, 0.00, NULL),
(21, 6, 'PRD006', 'WHITE T-SHIRT', 'sdx', 100.00, 30.00, 0.00, 94.00, 'Piece', 0, 0.00, '', 14, '2025-07-16 15:11:48', '2025-07-16 15:49:26', 0, 0.00, NULL),
(22, 6, 'PRD007', 'fan', 'sas', 100.00, 80.00, 0.00, 88.00, 'Piece', 0, 0.00, '0', 14, '2025-07-16 15:42:10', '2025-07-23 12:50:41', 0, 0.00, NULL),
(25, 8, 'PRD003', 'Cotton Polo T-Shirt', 'Soft Cotton polo tee with collar,available in multiple colors', 699.00, 500.00, 0.00, 0.00, 'Piece', 0, 0.00, '0', 17, '2025-07-17 17:52:14', '2025-07-25 12:38:59', 0, 10.00, NULL),
(31, 8, 'PRD011', 'Sky Blue Jeans', '0', 3000.00, 2300.00, 0.00, 0.00, 'Piece', 10, 3.00, '0', 16, '2025-07-21 12:08:20', '2025-07-25 12:38:59', 0, 10.00, NULL),
(32, 9, 'PRD001', 'Grand Mother Story Book', '0', 100.00, 75.00, 0.00, 99.00, 'Piece', 0, 0.00, '0', 23, '2025-07-21 13:11:30', '2025-07-21 13:11:56', 0, 30.00, NULL),
(33, 9, 'PRD002', 'The Alchemist', '0', 299.00, 180.00, 0.00, 19.00, 'Piece', 5, 10.00, '0', 22, '2025-07-21 13:20:32', '2025-07-21 13:22:14', 0, 5.00, NULL),
(38, 6, 'PRD101', 'Sky Blue Jeans', '0', 100.00, NULL, 0.00, 100.00, 'Piece', 0, 0.00, '0', 14, '2025-07-23 12:59:20', '2025-07-23 12:59:20', 0, 5.00, NULL),
(39, 6, 'PRD0123', 'Sky Blue Jeans', '0', 300.00, NULL, 0.00, 100.00, 'Piece', 0, 0.00, '0', 14, '2025-07-23 13:03:51', '2025-07-23 13:18:56', 0, 10.00, NULL),
(40, 6, 'PRD0066', 'Sky Blue Jeans', '0', 1234.00, NULL, 0.00, 123.00, 'Piece', 0, 0.00, '0', 14, '2025-07-23 13:29:36', '2025-07-23 13:29:36', 0, 11.00, NULL),
(41, 6, 'PRD00634', 'Sky Blue Jeans', 'dnkjasdnk', 167.00, NULL, 0.00, 123.00, 'Piece', 0, 0.00, 'uploads/prod_6880e40d30e43.jpg', 14, '2025-07-23 13:30:53', '2025-07-23 13:30:53', 0, 0.00, NULL),
(42, 6, 'PRD00534', 'Sky Blue Jeans', '0', 1234.00, NULL, 0.00, 12.00, 'Piece', 0, 0.00, '0', 14, '2025-07-23 13:35:40', '2025-07-23 13:35:40', 0, 12.00, NULL),
(43, 6, 'PRD0022', 'Sky Blue Jeans', '0', 1234.00, NULL, 0.00, 12.00, 'Piece', 0, 0.00, 'uploads/prod_6880e6f2a25e5.jpg', 14, '2025-07-23 13:43:14', '2025-07-23 13:43:14', 0, 3.00, NULL),
(44, 6, 'PRD00234', 'Sky Blue Jeans', '0', 1312.00, NULL, 0.00, 123.00, 'Piece', 0, 0.00, 'uploads/prod_6880e88b61754.jpg', 14, '2025-07-23 13:50:03', '2025-07-23 13:50:03', 0, 11.00, NULL),
(45, 6, 'PRD0101', 'Sky Blue Jeans', '0', 11.11, 11111.00, 0.00, 11.00, 'Kg', 0, 0.00, '', 14, '2025-07-23 13:51:05', '2025-07-23 13:51:05', 0, 1111.00, NULL),
(46, 6, 'PRD010101', 'RED T-SHIRT', '0', 11.11, 11111.00, 0.00, 111.00, 'Kg', 0, 0.00, 'uploads/prod_6880eaebd760e.jpg', 14, '2025-07-23 13:53:29', '2025-07-23 14:00:11', 0, 1111.00, NULL),
(47, 6, 'PRD1234', 'RED T-SHIRT', '0', 1111.00, 1111.00, 0.00, 1112.00, 'Piece', 0, 0.00, 'uploads/prod_6880eb589a2e9.jpg', 14, '2025-07-23 14:02:00', '2025-07-23 14:02:00', 0, 0.00, NULL),
(48, 6, 'PRD00500', 'Sky Blue Jeans', '0', 245.00, NULL, 0.00, 123.00, 'Piece', 0, 0.00, 'uploads/prod_6880eec9ae850.jpg', 14, '2025-07-23 14:16:41', '2025-07-23 14:16:41', 0, 0.00, NULL),
(50, 12, 'FASH001', 'Men’s Cotton T-Shirt', '0', 499.00, 350.00, 0.00, 49.00, 'Piece', 2, 5.00, 'uploads/prod_68822905465fc.jpeg', 25, '2025-07-24 12:37:25', '2025-08-25 16:00:51', 0, 10.00, NULL),
(51, 12, 'FASH002', 'Rayon Printed Kurti', '0', 749.00, 600.00, 0.00, 57.00, 'Piece', 0, 0.00, 'uploads/prod_688229b510dbf.jpg', 26, '2025-07-24 12:40:21', '2025-08-23 12:38:31', 0, 10.00, NULL),
(52, 12, 'FASH003', 'Slim Fit Stretchable Jeans', 'Slim Fit Stretchable Jeans', 1199.00, 950.00, 0.00, 76.00, 'Piece', 0, 0.00, 'uploads/prod_68822b022b5ff.jpg', 27, '2025-07-24 12:45:54', '2025-08-19 13:41:21', 0, 10.00, NULL),
(59, 12, 'FASH004', 'Levi\\\'s Men\\\'s 511 Slim Fit Jeans', 'A modern slim with room to move, the 511 Slim Fit Stretch Jeans are a classic, you can wear with anything. Complete your casual wardrobe with a splash of style with these 511 slim fit jeans from Levi\'s. Cut in a cotton fabric with the right amount of stretch, these navy jeans in solid pattern can be paired with most things to create a super cool outfit.\r\n\r\n1. These jeans sit at the waist with a slim fit from hip to ankle.\r\n2. It is woven, with a hint of stretch, to deliver maximum comfort.\r\n3. Cut close to the body, the 511 Slim is a great alternative to the skinny jeans.', 1000.00, 750.00, 0.00, 92.00, 'Piece', 5, 5.00, 'uploads/prod_68a4806f69bc6.jpg', 27, '2025-08-19 13:47:27', '2025-08-19 13:50:46', 0, 10.00, NULL),
(61, 12, 'FASH005', 'Utsa Indigo Foliage Pattern Kurti', 'Description: Kurti\\r\\nDimensions: 81cm\\r\\nThis indigo kurti from Utsa showcases intricate foliage prints. Created from premium fabric, it’s designed with a spread collar that extends to a button placket and full sleeves for a refined touch.\\r\\n\\r\\nNet Quantity: 1N\\r\\nFit: Regular Fit\\r\\nCare Instruction: Machine Wash\\r\\nFabric Composition: 100% Polyester\\r\\nModel Fit: The model is 5ft 8 inches and is wearing size S\\r\\nManufactured and Marketed By:\\r\\nTrent Limited, Bombay House, 24, Homi Mody Street, Fort, Mumbai – 400001\\r\\n\\r\\nCountry Of Origin: India', 799.00, 500.00, 0.00, 23.00, 'Piece', 2, 5.00, '', 26, '2025-08-19 13:59:07', '2025-08-19 14:00:02', 0, 5.00, NULL),
(62, 12, 'FASH006', '3bros Men\\\'s Solid Polo Neck T-shirt', '3BROS Offers Quality Casual Wear In The Widest Variety Which Makes It Easy To Choose. Fall In Love With The Soft Texture Of The Fabric Wearing This Dark Firoji Plain Solid Tshirt, Has a Polo Collar & Half Sleeves Slim-Fit T-Shirt By 3BROS. Hook Up With Comfort And Roll With Time As You Adorn This T-Shirt Fashioned Using Cotton Pique. Work Hard And Play Harder As You Party Through The Night In This Utterly Comfortable T-Shirt. These T-Shirt Can Be Teamed With A Pair Of Sneakers & Jeans To Look Your Best.', 500.00, 425.00, 0.00, 95.00, 'Piece', 5, 2.00, '', 25, '2025-08-22 13:24:58', '2025-08-25 16:00:33', 0, 10.00, NULL),
(80, 10, 'PROD999', 'Wheat', '', 100.00, 0.00, 0.00, 5.50, '0', 0, 0.00, '', 24, '2025-08-23 17:48:59', '2025-08-23 17:48:59', 0, 0.00, NULL),
(81, 10, 'GROC005', 'Fresh Apples (1kg)', '', 199.00, 0.00, 0.00, 4.40, '0', 0, 0.00, '', 24, '2025-08-23 17:48:59', '2025-08-23 17:48:59', 1, 0.00, NULL),
(83, 10, 'PROD118', 'Basmati Rice 5kg', '', 1000.00, 0.00, 0.00, 5.50, '0', 0, 0.00, '', 24, '2025-08-23 17:51:08', '2025-08-23 17:51:08', 0, 0.00, NULL),
(86, 10, 'PROD112', 'Wheat', '', 100.00, 0.00, 0.00, 100.00, '0', 0, 0.00, '', 24, '2025-08-23 18:24:41', '2025-08-23 18:24:41', 0, 0.00, NULL),
(107, 15, 'PRD1', 'Basmati Rice 5kg', '', 89.00, 0.00, 0.00, 92.00, 'Piece', 0, 0.00, '', 34, '2025-08-25 12:03:23', '2025-08-25 13:57:53', 0, 0.00, NULL),
(108, 15, 'PRD2', 'Fresh Apples (1kg)', '', 100.00, 0.00, 0.00, 99.00, 'Kg', 0, 0.00, '', 35, '2025-08-25 12:03:23', '2025-08-25 12:46:27', 0, 0.00, NULL),
(109, 15, 'PRD3', 'ANKSDS', '', 100.00, 0.00, 0.00, 99.00, 'Set', 0, 0.00, '', 33, '2025-08-25 12:03:23', '2025-08-25 12:46:27', 0, 0.00, NULL),
(110, 15, 'PRD4', 'Canned Tomatoes', '', 100.00, 0.00, 0.00, 99.00, 'Box', 0, 0.00, '', 35, '2025-08-25 12:03:23', '2025-08-25 12:46:27', 0, 0.00, NULL),
(111, 15, 'PRD5', 'Milk (1 Litre)', '', 100.00, 100.00, 0.00, 93.00, 'Meter', 0, 0.00, '', 34, '2025-08-25 12:03:24', '2025-08-25 14:30:20', 0, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_attribute_values`
--

CREATE TABLE `product_attribute_values` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_attribute_values`
--

INSERT INTO `product_attribute_values` (`id`, `product_id`, `attribute_name`, `attribute_value`, `created_at`) VALUES
(6, 17, 'color', 'Black', '2025-07-16 13:34:24'),
(7, 17, 'size', 'M', '2025-07-16 13:34:24'),
(8, 18, 'color', 'Black', '2025-07-16 14:44:54'),
(9, 19, 'color', 'white', '2025-07-16 14:45:46'),
(10, 20, 'color', 'Black', '2025-07-16 15:10:24'),
(12, 21, 'color', 'white', '2025-07-16 15:49:26'),
(27, 25, 'Color', 'Black', '2025-07-21 10:02:25'),
(28, 25, 'Size', 'M', '2025-07-21 10:02:25'),
(60, 31, 'Color', 'Sky Blue', '2025-07-21 12:35:29'),
(61, 31, 'Size', '30', '2025-07-21 12:35:29'),
(64, 33, 'Type', 'Paperback', '2025-07-21 13:20:32'),
(65, 33, 'Language', 'English', '2025-07-21 13:20:32'),
(66, 33, 'Author', 'Paulo Coelho', '2025-07-21 13:20:32'),
(74, 47, 'Brand', 'white', '2025-07-23 14:02:00'),
(85, 51, 'Size', 'L', '2025-07-24 12:40:21'),
(86, 51, 'Color', 'Green', '2025-07-24 12:40:21'),
(87, 51, 'Fabric', 'Rayon', '2025-07-24 12:40:21'),
(98, 52, 'Waist', '32', '2025-08-19 13:41:21'),
(99, 52, 'Length', '40', '2025-08-19 13:41:21'),
(100, 52, 'Color', 'Blue', '2025-08-19 13:41:21'),
(104, 59, 'Size', '30', '2025-08-19 13:47:45'),
(105, 59, 'length', '34', '2025-08-19 13:47:45'),
(106, 59, 'color', 'blue', '2025-08-19 13:47:45'),
(109, 61, 'Fabric', 'Polyster', '2025-08-19 13:59:07'),
(110, 61, 'Size', 'L', '2025-08-19 13:59:07'),
(129, 50, 'Size', 'M', '2025-08-24 05:26:22'),
(130, 50, 'Color', 'Navy Blue', '2025-08-24 05:26:22'),
(131, 50, 'Fabric', 'Cotton', '2025-08-24 05:26:22'),
(133, 62, 'Brand', '3BROS', '2025-08-25 16:00:33'),
(134, 62, 'Type', 'Polo Neck', '2025-08-25 16:00:33'),
(135, 62, 'Sleeve', 'Short Sleeve', '2025-08-25 16:00:33'),
(136, 62, 'Fabric', 'Cotton Blend', '2025-08-25 16:00:33'),
(137, 62, 'Size', 'M', '2025-08-25 16:00:33'),
(138, 62, 'Color', 'Pink', '2025-08-25 16:00:33');

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(11) NOT NULL,
  `shop_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `owner_name` varchar(255) NOT NULL,
  `owner_email` varchar(255) NOT NULL,
  `owner_mobile` varchar(15) NOT NULL,
  `shop_image_path` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `gst_number` varchar(20) DEFAULT NULL,
  `shop_license` varchar(100) DEFAULT NULL,
  `auto_discount` tinyint(1) NOT NULL DEFAULT 0,
  `show_gst_license` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `shop_name`, `address`, `owner_name`, `owner_email`, `owner_mobile`, `shop_image_path`, `password_hash`, `created_at`, `updated_at`, `registration_date`, `gst_number`, `shop_license`, `auto_discount`, `show_gst_license`) VALUES
(5, 'Vishal Electronics', 'Amreli 89', 'vishal makwana', 'admin@email.com', '7777788888', 'uploads/shop_images/shop_686a2595bcdf2.png', '$2y$10$1KfrV04.I3gn8SntETE0Mu1L5Ndo1ordQBU3IvCcDoeqd93/6.YtC', '2025-07-06 06:55:49', '2025-07-06 07:28:21', '2025-07-06 06:55:49', '27ABCDE1234F1Z5', 'LS07688907', 0, 1),
(6, 'Parth Electronics', 'parth amreli', 'parth', 'parth@email.com', '9090906060', 'uploads/shop_images/shop_6880de5beee76.png', '$2y$10$zwbxXThihcij7Ppnsx1FSeygFMd.4AZ3Cn86P.8TLKOIengHbGHAi', '2025-07-13 07:01:00', '2025-07-23 13:06:35', '2025-07-13 07:01:00', '', '', 0, 1),
(8, 'Vishal Mens Wear', 'Vishal Mens Wear Amreli', 'Vishal', 'abc@email.com', '7779082845', 'uploads/shop_images/shop_6879354f60f95.png', '$2y$10$B5J4oarSlYfuJxNA.On3U.nx2diCGB5RgOw24Cmv61x5M4jt8qAMS', '2025-07-17 17:39:27', NULL, '2025-07-17 17:39:27', '', '', 0, 1),
(9, 'MyBook', 'MyBook Amreli Near Chital Road', 'mayur', 'mayur@email.com', '8890789067', '', '$2y$10$lj5bzg.t4VhaCdrfdANYp.zm4l5T2WAR8dY0QtCUGorZ.MUh54EEy', '2025-07-21 13:08:25', NULL, '2025-07-21 13:08:25', '27ABCDE1234F1Z11', 'LS009111123', 0, 1),
(10, 'All In One Grocery Store', 'All In One Grocery Store Amreli', 'mohan', 'mohan@email.com', '7779082842', 'uploads/shop_images/shop_6880f78dab171.png', '$2y$10$KqBXDHxUlQDx8ZCC.eTQtuSm5fT/zPnsoh/3QDz.gxGtXKC0ZvdMW', '2025-07-21 13:25:37', '2025-08-09 14:29:21', '2025-07-21 13:25:37', '27ABCDE1234F1Z5', 'LS009111122', 0, 1),
(12, 'FashionHub', '102, MG Road, Mumbai, MH - 400001', 'Rina Mehta', 'rina.fashionhub@gmail.com', '9876543210', 'uploads/shop_images/shop_688375a6d22e8.png', '$2y$10$7bNkV3ZjA6TZmQGoU.BVNu76Ie/ooo4UqOW.Du3Hb9u0ZA9q12O3.', '2025-07-24 12:31:03', '2025-08-23 14:28:29', '2025-07-24 12:31:03', '27ABCDE1234F1Z2', 'LIC-CLOTH-2025', 0, 1),
(13, 'FashionHub', 'FashionHub', 'Vishal', 'hkl@email.com', '9090906066', '', '$2y$10$TXIgqFyboEhkZVZ5Qr2UhOuOcIyezRJOflb7e3Ft6damW0x29e6le', '2025-07-29 12:09:43', NULL, '2025-07-29 12:09:43', '27ABCDE1234F1Z9', 'LS009111120', 0, 1),
(14, 'FreshMart Grocery', '45, MG Road, Near City Mall, Ahmedabad, Gujarat, 380009', 'Rohan Patel', 'rohan@email.com', '9876543211', 'uploads/shop_images/shop_68aa8c0266579.jpeg', '$2y$10$dmz.Sq/Kof4jDXQoOzFuxeOIJ10TIjPqS.AfBbMh5cgOZgeihxryG', '2025-08-24 03:47:26', '2025-08-24 04:46:40', '2025-08-24 03:47:26', '27ABCDE1234F1Z6', 'LS009122334', 0, 1),
(15, 'Parth Electronics', 'Amreli', 'parth', 'parthelectronics@email.com', '9090906061', '', '$2y$10$TikH9wGawLpAbS/JaVj8JetL..Ugq9cgefUagDMjsOkx3/yEvIPjO', '2025-08-25 10:29:30', NULL, '2025-08-25 10:29:30', '', '', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `shop_settings`
--

CREATE TABLE `shop_settings` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `show_gst` tinyint(1) DEFAULT 1,
  `show_license` tinyint(1) DEFAULT 1,
  `show_discounts` tinyint(1) DEFAULT 1,
  `header_message` text DEFAULT NULL,
  `footer_message` text DEFAULT NULL,
  `auto_discount` tinyint(1) NOT NULL DEFAULT 0,
  `bill_footer` text DEFAULT NULL,
  `low_stock_threshold` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_settings`
--

INSERT INTO `shop_settings` (`id`, `shop_id`, `show_gst`, `show_license`, `show_discounts`, `header_message`, `footer_message`, `auto_discount`, `bill_footer`, `low_stock_threshold`) VALUES
(1, 5, 1, 1, 1, NULL, NULL, 0, NULL, 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shop_id` (`shop_id`,`name`);

--
-- Indexes for table `contact_requests`
--
ALTER TABLE `contact_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `pending_bills`
--
ALTER TABLE `pending_bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shop_id` (`shop_id`,`product_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `owner_email` (`owner_email`),
  ADD UNIQUE KEY `owner_mobile` (`owner_mobile`);

--
-- Indexes for table `shop_settings`
--
ALTER TABLE `shop_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=263;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `contact_requests`
--
ALTER TABLE `contact_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pending_bills`
--
ALTER TABLE `pending_bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `shop_settings`
--
ALTER TABLE `shop_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contact_requests`
--
ALTER TABLE `contact_requests`
  ADD CONSTRAINT `contact_requests_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pending_bills`
--
ALTER TABLE `pending_bills`
  ADD CONSTRAINT `pending_bills_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_settings`
--
ALTER TABLE `shop_settings`
  ADD CONSTRAINT `shop_settings_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
