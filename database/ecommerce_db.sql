
Folder highlights
Database and PHP files detail an e-commerce platform including product listings, user activity logs, and coupon data up to Jan 02, 2026.

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 02, 2026 at 03:13 PM
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
-- Database: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `Log_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Action` varchar(255) NOT NULL,
  `Details` varchar(255) DEFAULT NULL,
  `Timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`Log_ID`, `User_ID`, `Action`, `Details`, `Timestamp`) VALUES
(1, 16, 'Login', 'User logged in successfully', '2025-12-07 11:17:59'),
(2, 15, 'Login', 'User logged in successfully', '2025-12-07 11:20:04'),
(3, 16, 'Login', 'User logged in successfully', '2025-12-07 11:27:19'),
(4, 15, 'Login', 'User logged in successfully', '2025-12-07 11:33:25'),
(5, 16, 'Login', 'User logged in successfully', '2025-12-07 12:06:59'),
(6, 15, 'Login', 'User logged in successfully', '2025-12-10 13:14:53'),
(8, 15, 'Login', 'User logged in successfully', '2025-12-10 13:18:04'),
(11, 15, 'Login', 'User logged in successfully', '2025-12-10 19:24:30'),
(12, 15, 'Login', 'User logged in successfully', '2025-12-10 23:15:33'),
(13, 15, 'Login', 'User logged in successfully', '2025-12-15 09:07:31'),
(14, 15, 'Login', 'User logged in successfully', '2025-12-15 09:10:09'),
(15, 15, 'Login', 'User logged in successfully', '2025-12-15 09:47:43'),
(16, 15, 'Login', 'User logged in successfully', '2025-12-15 13:12:16'),
(18, 15, 'Login', 'User logged in successfully', '2025-12-15 13:14:56'),
(20, 15, 'Login', 'User logged in successfully', '2025-12-15 13:15:58'),
(22, 15, 'Login', 'User logged in successfully', '2026-01-02 20:33:42');

-- --------------------------------------------------------

--
-- Table structure for table `analytics_report`
--

CREATE TABLE `analytics_report` (
  `Report_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Total_Sales` int(11) NOT NULL,
  `Total_Revenues` int(11) NOT NULL,
  `Avg_Ratings` int(11) NOT NULL,
  `Review_Count` int(11) NOT NULL,
  `Rank` varchar(255) NOT NULL,
  `Report_Date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `Cart_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_item`
--

CREATE TABLE `cart_item` (
  `Cart_Item_ID` int(11) NOT NULL,
  `Cart_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Quantity` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `Category_ID` int(11) NOT NULL,
  `Category_Name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`Category_ID`, `Category_Name`) VALUES
(1, 'Academic Supplies'),
(2, 'Tech Gadgets'),
(3, 'Personal Items'),
(4, 'Dorm Essentials');

-- --------------------------------------------------------

--
-- Table structure for table `coupon`
--

CREATE TABLE `coupon` (
  `promo_id` int(12) NOT NULL,
  `type` varchar(32) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `discount_type` varchar(10) NOT NULL DEFAULT 'fixed',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_exp` date NOT NULL,
  `amount` decimal(5,2) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `claimed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupon`
--

INSERT INTO `coupon` (`promo_id`, `type`, `code`, `description`, `discount_type`, `date_created`, `date_exp`, `amount`, `status`, `claimed_by`) VALUES
(34, 'CHRISTMAS SALE! 12/24/25!', 'XMAS24', 'CHRISTAMAS SALE!!! UNTIL DECEMBER 24!!', 'fixed', '2025-12-07 11:45:46', '2025-12-24', 120.00, 2, NULL),
(36, 'DECEMBER 12 500 SALE!!!!!', 'DEC1212', 'DECEMBER 12, 2025 500 PESO SALEE!!! ENJOY LIMITED OFFER UNTIL 12/12/25', 'fixed', '2025-12-07 12:01:28', '2025-12-12', 500.00, 2, NULL),
(39, 'MISS JAY SALE!!!! 250', 'MSJ250', 'MSIS JAY SALE BECAUSE SHE IS HAPPY!!! 250 PESOS DISCOUNT TO YOUR ORDER', 'fixed', '2025-12-15 10:06:30', '2025-12-16', 250.00, 2, 18);

-- --------------------------------------------------------

--
-- Table structure for table `loyalty`
--

CREATE TABLE `loyalty` (
  `Loyalty_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Current_Points` int(11) NOT NULL,
  `Total_Points` int(11) NOT NULL,
  `Tier_Level` varchar(255) NOT NULL,
  `Status` varchar(255) NOT NULL,
  `Last_Activity_Date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `Notification_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Message` varchar(255) NOT NULL,
  `Type` varchar(255) NOT NULL,
  `Status` varchar(255) NOT NULL,
  `Timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `Order_Detail_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Price` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`Order_Detail_ID`, `Order_ID`, `Product_ID`, `Quantity`, `Price`) VALUES
(54, 64, 16, 1, 1350),
(55, 65, 20, 10, 380),
(56, 66, 23, 5, 350),
(57, 67, 23, 15, 350),
(58, 68, 22, 1, 499),
(59, 69, 20, 1, 380),
(60, 70, 20, 1, 380),
(61, 71, 18, 2, 1250),
(62, 72, 22, 1, 499),
(63, 73, 20, 1, 380),
(64, 74, 22, 1, 499),
(65, 75, 20, 1, 380),
(66, 76, 20, 1, 380),
(67, 77, 23, 1, 350),
(68, 78, 18, 1, 1250),
(69, 79, 15, 1, 5000),
(70, 80, 18, 1, 1250),
(71, 81, 20, 1, 380),
(72, 82, 18, 1, 1250),
(73, 83, 22, 1, 499),
(75, 85, 21, 1, 899),
(76, 86, 18, 1, 1250),
(77, 87, 22, 1, 499),
(78, 88, 18, 1, 1250),
(79, 89, 18, 1, 1250),
(80, 90, 18, 1, 1250),
(81, 91, 18, 1, 1250),
(82, 92, 22, 1, 499),
(83, 93, 17, 1, 1080),
(84, 94, 23, 1, 350),
(85, 95, 21, 1, 899),
(86, 96, 22, 1, 499),
(87, 97, 18, 19, 1250),
(88, 98, 18, 20, 1250),
(89, 99, 20, 1, 380),
(90, 100, 20, 5, 380),
(91, 101, 20, 1, 380),
(92, 102, 20, 1, 380),
(93, 103, 20, 1, 380),
(94, 104, 20, 25, 380),
(95, 105, 20, 8, 380),
(96, 106, 17, 2, 1080),
(97, 107, 22, 13, 499),
(98, 108, 17, 1, 1080);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Payment_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Customer_Name` varchar(255) DEFAULT NULL,
  `Method` varchar(255) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Price` int(11) NOT NULL,
  `Amount` int(11) NOT NULL,
  `Total_Amount` decimal(10,2) DEFAULT NULL,
  `Status` varchar(255) NOT NULL,
  `Order_Status` varchar(100) DEFAULT NULL,
  `Reference_Num` varchar(255) NOT NULL,
  `Order_Details` text DEFAULT NULL,
  `Shipping_Address` text DEFAULT NULL,
  `Order_Date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`Payment_ID`, `Order_ID`, `User_ID`, `Customer_Name`, `Method`, `Quantity`, `Price`, `Amount`, `Total_Amount`, `Status`, `Order_Status`, `Reference_Num`, `Order_Details`, `Shipping_Address`, `Order_Date`) VALUES
(53, 65, 16, 'Jeremiah Roilo', 'Credit Card', 10, 380, 3800, 3800.00, 'Pending', 'Delivered', 'REF20251207906909', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":10,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":50,\"removed\":false,\"selected\":true}', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', '2025-12-07 13:58:05'),
(54, 66, 16, 'Jeremiah Roilo', 'Cash on Delivery', 5, 350, 1750, 1750.00, 'Pending', 'Confirmed', 'REF20251207802692', '{\"id\":23,\"name\":\"Kingston 64GB USB Flash Drive\",\"price\":350,\"qty\":5,\"img\":\"images\\/product_6935139bd9e4b2.06730805.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":60,\"removed\":false,\"selected\":true}', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', '2025-12-07 14:02:21'),
(55, 67, 17, 'Seon Soquena', 'Cash on Delivery', 15, 350, 5250, 5250.00, 'Pending', 'Cancelled', 'REF20251207802696', '{\"id\":23,\"name\":\"Kingston 64GB USB Flash Drive\",\"price\":350,\"qty\":15,\"img\":\"images\\/product_6935139bd9e4b2.06730805.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":55,\"removed\":false,\"selected\":true}', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', '2025-12-07 14:05:17'),
(56, 68, 17, 'Seon Soquena', 'Cash on Delivery', 1, 499, 499, 499.00, 'Pending', 'Cancelled', 'REF20251207445708', '{\"id\":22,\"name\":\"DESIRETECH White Electric Extension Lead 4 Gang 2 Metre \",\"price\":499,\"qty\":1,\"img\":\"images\\/product_693512361509c6.16331725.jpg\",\"category\":\"Dorm Essentials\",\"max_stock\":20,\"removed\":false,\"selected\":true}', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', '2025-12-07 14:09:07'),
(57, 69, 18, 'rnzo', 'Credit Card', 1, 380, 380, 380.00, 'Pending', 'Delivered', 'REF20251210951905', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":40,\"removed\":false,\"selected\":true}', 'Bacolod brgy katilingban, bacolod 6100', '2025-12-10 13:17:09'),
(58, 70, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Confirmed', 'REF20251210804337', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":39,\"removed\":false,\"selected\":true}', 'bacolod mansilingan, bacolod 6100', '2025-12-10 13:19:43'),
(59, 71, 18, 'rnzo', 'Cash on Delivery', 2, 1250, 2500, 2500.00, 'Pending', 'Confirmed', 'REF20251210450780', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":2,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":30,\"removed\":false,\"selected\":true}', 'gasdasdas, bacolod 6100', '2025-12-10 19:26:41'),
(60, 72, 18, 'rnzo', 'Cash on Delivery', 1, 499, 499, 499.00, 'Pending', 'Delivered', 'REF20251210407521', '{\"id\":22,\"name\":\"DESIRETECH White Electric Extension Lead 4 Gang 2 Metre \",\"price\":499,\"qty\":1,\"img\":\"images\\/product_693512361509c6.16331725.jpg\",\"category\":\"Dorm Essentials\",\"max_stock\":19,\"removed\":false,\"selected\":true}', 'Purok katilingban baranggay banago, Bacolod 6100', '2025-12-10 19:43:59'),
(61, 73, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Shipped', 'REF20251210874588', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":38,\"removed\":false,\"selected\":true}', 'dasdasd, asdasdas 6100', '2025-12-10 20:01:45'),
(62, 74, 18, 'rnzo', 'Cash on Delivery', 1, 499, 499, 499.00, 'Pending', 'Delivered', 'REF20251210865492', '{\"id\":22,\"name\":\"DESIRETECH White Electric Extension Lead 4 Gang 2 Metre \",\"price\":499,\"qty\":1,\"img\":\"images\\/product_693512361509c6.16331725.jpg\",\"category\":\"Dorm Essentials\",\"max_stock\":18,\"removed\":false,\"selected\":true}', 'Katilingban banago, bacolod 6100', '2025-12-10 20:18:01'),
(63, 75, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Confirmed', 'REF20251210410297', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":37,\"removed\":false,\"selected\":true}', 'Banago, bacolod 6100', '2025-12-10 21:36:18'),
(64, 76, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Confirmed', 'REF20251210382084', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":36,\"removed\":false,\"selected\":true}', 'Taculing, Bacolod 6100', '2025-12-10 21:59:58'),
(65, 77, 18, 'rnzo', 'Cash on Delivery', 1, 350, 350, 350.00, 'Pending', 'Confirmed', 'REF20251210567184', '{\"id\":23,\"name\":\"Kingston 64GB USB Flash Drive\",\"price\":350,\"qty\":1,\"img\":\"images\\/product_6935139bd9e4b2.06730805.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":40,\"removed\":false,\"selected\":true}', 'Banago, Bacolod 6100', '2025-12-10 22:02:06'),
(66, 78, 18, 'rnzo', 'Cash on Delivery', 1, 1250, 1250, 6250.00, 'Pending', 'Confirmed', 'REF20251210464635', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":1,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":28,\"removed\":false,\"selected\":true}', 'Banago, bacolod 6100', '2025-12-10 22:47:17'),
(67, 79, 18, 'rnzo', 'Cash on Delivery', 1, 5000, 5000, 6250.00, 'Pending', 'Cancelled', 'REF20251210570303', '{\"id\":15,\"name\":\"Enuosuma Mini Projecter\",\"price\":5000,\"qty\":1,\"img\":\"images\\/product_6934e4bbeeed10.73925095.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":10,\"removed\":false,\"selected\":true}', 'Banago, bacolod 6100', '2025-12-10 22:47:17'),
(68, 80, 18, 'rnzo', 'Cash on Delivery', 1, 1250, 1250, 1250.00, 'Pending', 'Delivered', 'REF20251210901614', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":1,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":27,\"removed\":false,\"selected\":true}', 'Taculing, Bacolod 6100', '2025-12-10 22:59:26'),
(69, 81, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Confirmed', 'REF20251210684906', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":35,\"removed\":false,\"selected\":true}', 'Estefania, bacolod 6100', '2025-12-10 23:10:16'),
(70, 82, 18, 'rnzo', 'Cash on Delivery', 1, 1250, 1250, 1250.00, 'Pending', 'Confirmed', 'REF20251210156329', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":1,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":26,\"removed\":false,\"selected\":true}', 'Banago, bacolod 6100', '2025-12-10 23:22:36'),
(71, 83, 18, 'rnzo', 'Credit Card', 1, 499, 499, 499.00, 'Pending', 'Delivered', 'REF20251210940070', '{\"id\":22,\"name\":\"DESIRETECH White Electric Extension Lead 4 Gang 2 Metre \",\"price\":499,\"qty\":1,\"img\":\"images\\/product_693512361509c6.16331725.jpg\",\"category\":\"Dorm Essentials\",\"max_stock\":17,\"removed\":false,\"selected\":true}', 'Estefania, bacolod 6100', '2025-12-10 23:35:40'),
(73, 85, 18, 'rnzo', 'Cash on Delivery', 1, 899, 899, 899.00, 'Pending', 'Delivered', 'REF20251210324702', '{\"id\":21,\"name\":\"Stainless Steel Insulated Hiking Bottle 1 L Blue\",\"price\":899,\"qty\":1,\"img\":\"images\\/product_69351088102bd6.65811846.jpg\",\"category\":\"Personal Items\",\"max_stock\":15,\"removed\":false,\"selected\":true}', 'Tangub, bacolod 6100', '2025-12-10 23:56:50'),
(74, 86, 18, 'rnzo', 'Cash on Delivery', 1, 1250, 1250, 1250.00, 'Pending', 'Delivered', 'REF20251210473386', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":1,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":24,\"removed\":false,\"selected\":true}', 'Barangay 35, Bacolod City, bacolod 6100', '2025-12-11 00:43:05'),
(75, 87, 18, 'rnzo', 'Cash on Delivery', 1, 499, 499, 499.00, 'Pending', 'Delivered', 'REF20251210849051', '{\"id\":22,\"name\":\"DESIRETECH White Electric Extension Lead 4 Gang 2 Metre \",\"price\":499,\"qty\":1,\"img\":\"images\\/product_693512361509c6.16331725.jpg\",\"category\":\"Dorm Essentials\",\"max_stock\":16,\"removed\":false,\"selected\":true}', 'Barangay 3, Bacolod, bacolod 6100', '2025-12-11 01:06:19'),
(76, 88, 18, 'rnzo', 'Cash on Delivery', 1, 1250, 1250, 1250.00, 'Pending', 'Delivered', 'REF20251210435724', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":1,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":23,\"removed\":false,\"selected\":true}', 'Bata, Bacolod, bacolod 6100', '2025-12-11 01:12:48'),
(77, 89, 18, 'rnzo', 'Cash on Delivery', 1, 1250, 1250, 1250.00, 'Pending', 'Delivered', 'REF20251210194088', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":1,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":22,\"removed\":false,\"selected\":true}', 'Tangub, Bacolod city, Bacolod 6100', '2025-12-11 01:20:48'),
(78, 90, 18, 'rnzo', 'Cash on Delivery', 1, 1250, 1250, 1250.00, 'Pending', 'Delivered', 'REF20251210959552', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":1,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":21,\"removed\":false,\"selected\":true}', 'Alijis, Bacolod, bacolod 6100', '2025-12-11 01:29:44'),
(79, 91, 18, 'rnzo', 'Cash on Delivery', 1, 1250, 1250, 1250.00, 'Pending', 'Delivered', 'REF20251210532854', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":1,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":20,\"removed\":false,\"selected\":true}', 'Banago, Bacolod, Bacolod 6100', '2025-12-11 01:37:33'),
(80, 92, 18, 'rnzo', 'Cash on Delivery', 1, 499, 499, 499.00, 'Pending', 'Delivered', 'REF20251210242465', '{\"id\":22,\"name\":\"DESIRETECH White Electric Extension Lead 4 Gang 2 Metre \",\"price\":499,\"qty\":1,\"img\":\"images\\/product_693512361509c6.16331725.jpg\",\"category\":\"Dorm Essentials\",\"max_stock\":15,\"removed\":false,\"selected\":true}', 'Barangay 35, bacolod, bacolod 6100', '2025-12-11 01:41:38'),
(81, 93, 18, 'rnzo', 'Cash on Delivery', 1, 1080, 1080, 1080.00, 'Pending', 'Shipped', 'REF20251210707017', '{\"id\":17,\"name\":\"Hawk 5453 Backpack\",\"price\":1080,\"qty\":1,\"img\":\"images\\/product_6934f7abdca7f4.78923284.jpg\",\"category\":\"Personal Items\",\"max_stock\":5,\"removed\":false,\"selected\":true}', 'Estefania, Bacolod, bacolod 6100', '2025-12-11 01:45:10'),
(82, 94, 18, 'rnzo', 'Cash on Delivery', 1, 350, 350, 350.00, 'Pending', 'Shipped', 'REF20251210790987', '{\"id\":23,\"name\":\"Kingston 64GB USB Flash Drive\",\"price\":350,\"qty\":1,\"img\":\"images\\/product_6935139bd9e4b2.06730805.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":39,\"removed\":false,\"selected\":true}', 'Banago, Bacolod, bacolod 6100', '2025-12-11 01:50:26'),
(83, 95, 18, 'rnzo', 'Cash on Delivery', 1, 899, 899, 899.00, 'Pending', 'Shipped', 'REF20251210179249', '{\"id\":21,\"name\":\"Stainless Steel Insulated Hiking Bottle 1 L Blue\",\"price\":899,\"qty\":1,\"img\":\"images\\/product_69351088102bd6.65811846.jpg\",\"category\":\"Personal Items\",\"max_stock\":14,\"removed\":false,\"selected\":true}', 'Tangub, Bacolod, bacolod 6100', '2025-12-11 01:57:26'),
(84, 96, 18, 'rnzo', 'Cash on Delivery', 1, 499, 499, 499.00, 'Pending', 'Delivered', 'REF20251210164133', '{\"id\":22,\"name\":\"DESIRETECH White Electric Extension Lead 4 Gang 2 Metre \",\"price\":499,\"qty\":1,\"img\":\"images\\/product_693512361509c6.16331725.jpg\",\"category\":\"Dorm Essentials\",\"max_stock\":14,\"removed\":false,\"selected\":true}', 'Bata, Bacolod, bacolod 6100', '2025-12-11 02:05:28'),
(85, 97, 18, 'rnzo', 'Credit Card', 19, 1250, 23750, 23750.00, 'Pending', 'Cancelled', 'REF20251215152833', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":19,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":19,\"removed\":false,\"selected\":true}', 'Bata, purok bayabasan, Bacolod city, Bacolod 6100', '2025-12-15 09:21:31'),
(86, 98, 18, 'rnzo', 'Cash on Delivery', 20, 1250, 25000, 25000.00, 'Pending', 'Cancelled', 'REF20251215515792', '{\"id\":18,\"name\":\"Buy Casio FX-991ES Plus Scientific Calculator\",\"price\":1250,\"qty\":20,\"img\":\"images\\/product_69350f84d90a02.38525918.jpg\",\"category\":\"Tech Gadgets\",\"max_stock\":20,\"removed\":false,\"selected\":true}', 'Marapara 1, Brgy Bata, Bacolod city, Bacolod 6100', '2025-12-15 09:38:08'),
(87, 99, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Cancelled', 'REF20251215584176', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":34,\"removed\":false,\"selected\":true}', 'Baranggay Bata, Marapara 1, Bacolod City, Bacolod 6100', '2025-12-15 09:39:31'),
(88, 100, 18, 'rnzo', 'Cash on Delivery', 5, 380, 1900, 1900.00, 'Pending', 'Cancelled', 'REF20251215278866', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":5,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":33,\"removed\":false,\"selected\":true}', 'Baranggay 39, Bacolod City, Bacolod 6100', '2025-12-15 09:41:33'),
(89, 101, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Delivered', 'REF20251215152614', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":28,\"removed\":false,\"selected\":true}', 'Tangub, Bacolod city, Bacolod 6100', '2025-12-15 09:42:44'),
(90, 102, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Cancelled', 'REF20251215378007', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":27,\"removed\":false,\"selected\":true}', 'Barangay 39, Bacolod city, Bacolod 6100', '2025-12-15 09:45:21'),
(91, 103, 18, 'rnzo', 'Cash on Delivery', 1, 380, 380, 380.00, 'Pending', 'Cancelled', 'REF20251215648659', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":1,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":26,\"removed\":false,\"selected\":true}', 'Barangay 1, Bacolod city, Bacolod 6100', '2025-12-15 09:57:33'),
(92, 104, 18, 'rnzo', 'Cash on Delivery', 25, 380, 9500, 9500.00, 'Pending', 'Delivered', 'REF20251215122263', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":25,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":25,\"removed\":false,\"selected\":true}', 'Celine Homes, Bacolod 6100', '2025-12-15 10:09:18'),
(93, 105, 18, 'Rnzo Doromal', 'Cash on Delivery', 8, 380, 3040, 2790.00, 'Pending', 'Shipped', 'REF20251215232221', '{\"id\":20,\"name\":\"Cattleya Filler Notebook Set (10pcs)\",\"price\":380,\"qty\":8,\"img\":\"images\\/product_6935102daf75f8.06948743.jpg\",\"category\":\"Academic Supplies\",\"max_stock\":8,\"removed\":false,\"selected\":true}', 'Alijis, Bacolod City, Bacolod 6100', '2025-12-15 13:23:08'),
(94, 106, 20, 'seon juval', 'Credit Card', 2, 1080, 2160, 2160.00, 'Pending', 'Delivered', 'REF20260102896528', '{\"id\":17,\"name\":\"Hawk 5453 Backpack\",\"price\":1080,\"qty\":2,\"img\":\"images\\/product_6934f7abdca7f4.78923284.jpg\",\"category\":\"Personal Items\",\"max_stock\":4,\"removed\":false,\"selected\":true}', 'Pahanocoy, Bacolod 6100', '2026-01-02 20:37:44'),
(95, 107, 20, 'seon juval', 'Credit Card', 13, 499, 6487, 6487.00, 'Pending', 'Delivered', 'REF20260102171401', '{\"id\":22,\"name\":\"DESIRETECH White Electric Extension Lead 4 Gang 2 Metre \",\"price\":499,\"qty\":13,\"img\":\"images\\/product_693512361509c6.16331725.jpg\",\"category\":\"Dorm Essentials\",\"max_stock\":13,\"removed\":false,\"selected\":true}', 'Banago, Bacolod 6100', '2026-01-02 21:38:12'),
(96, 108, 20, 'seon juval', 'Credit Card', 1, 1080, 1080, 1080.00, 'Pending', 'Delivered', 'REF20260102192656', '{\"id\":17,\"name\":\"Hawk 5453 Backpack\",\"price\":1080,\"qty\":1,\"img\":\"images\\/product_6934f7abdca7f4.78923284.jpg\",\"category\":\"Personal Items\",\"max_stock\":2,\"removed\":false,\"selected\":true}', 'Bata, Bacolod 6100', '2026-01-02 21:39:26');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `Product_ID` int(11) NOT NULL,
  `Category_ID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `Price` int(11) NOT NULL,
  `Stock` varchar(255) NOT NULL,
  `Availability` varchar(255) NOT NULL,
  `Images` varchar(255) NOT NULL,
  `QR_Code_Path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`Product_ID`, `Category_ID`, `Name`, `Description`, `Price`, `Stock`, `Availability`, `Images`, `QR_Code_Path`) VALUES
(15, 2, 'Enuosuma Mini Projecter', 'Mini Projector, Support 4K 1080P Portable Projector with Dual Band WIFI6 5G Wireless 5.2 Bluetooth Android 11, 160 ANSI Lumens, 180° Rotatable, Auto Keystone Correction,35\"-130\" Screen Video Projector', 5000, '9', 'In Stock', 'images/product_6934e4bbeeed10.73925095.jpg', NULL),
(16, 1, 'PARKER Sonnet Rollerball Pen', 'PARKER Sonnet Rollerball Pen, Matte Black Lacquer with Gold Trim, Fine Point Black Ink Refill', 1350, '14', 'In Stock', 'images/product_6934f723d5fa85.39218472.jpg', NULL),
(17, 3, 'Hawk 5453 Backpack', 'The Hawk 5453 Backpack features anti-microbial protection and a casual design. Made of Durashield fabric, this versatile bag offers anti-theft functionality.', 1080, '15', 'In Stock', 'images/product_6934f7abdca7f4.78923284.jpg', NULL),
(18, 2, 'Buy Casio FX-991ES Plus Scientific Calculator', 'Essential scientific calculator for engineering and math students. 552 functions with high-resolution LCD.', 1250, '20', 'In Stock', 'images/product_69350f84d90a02.38525918.jpg', NULL),
(19, 4, 'LED Desk Lamp with Pen & Phone holder', 'LED Desk Lamp with Pen & Phone holder, USB Rechargeable Book Reading Light,Eye-Caring Study Lamp for Kids, Touch Table Lamp with Flexible Gooseneck for Home,Office,3 Color Modes & Stepless Dimming', 450, '20', 'In Stock', 'images/product_69350fe8b64d85.80162419.jpg', NULL),
(20, 1, 'Cattleya Filler Notebook Set (10pcs)', 'Buy Cattleya / Excellent Fillers [10 notebooks] online today! EXCELLENT FILLER , 10 pcs per set - Enjoy best prices with free shipping vouchers.', 380, '20', 'In Stock', 'images/product_6935102daf75f8.06948743.jpg', NULL),
(21, 3, 'Stainless Steel Insulated Hiking Bottle 1 L Blue', 'Simple, solid and very insulating, the reliable top opens in a quarter turn, cup included / 1 litre.\r\nNeed to keep your drinks hot or cold for several hours? Our designers have developed this solid, highly insulating bottle.', 899, '13', 'In Stock', 'images/product_69351088102bd6.65811846.jpg', NULL),
(22, 4, 'DESIRETECH White Electric Extension Lead 4 Gang 2 Metre ', 'DESIRETECH White Electric Extension Lead 4 Gang 2 Metre | 2m Long Cable | UK Plug 3 Pin Socket Outlet | Wall Mountable | Multi Socket Mains Strip For Home, Bedroom, Kitchen and Office (2 Pack)', 499, '20', 'In Stock', 'images/product_693512361509c6.16331725.jpg', NULL),
(23, 2, 'Kingston 64GB USB Flash Drive', 'Introducing Kingston DataTraveler Exodia USB Flash Drives in 32GB, 64GB, and 128GB capacities. Enjoy quick, convenient, and lightweight storage with Plug and Play USB 3.2 connectivity for seamless data transfer.', 350, '38', 'In Stock', 'images/product_6935139bd9e4b2.06730805.jpg', NULL),
(24, 1, 'WEMATE Large Pencil Case', 'Big Capacity Pencil Case: WEMATE large pencil case upgraded size 9*4.3*2.4in, it has a huge storage space that can hold up to 80 pencils, and can easily hold a calculator and a 20cm ruler, and can store small stationery in the mesh bag.', 599, '50', 'In Stock', 'images/product_693514fc826547.64028291.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `Review_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Rating` varchar(255) NOT NULL,
  `Comment` varchar(255) NOT NULL,
  `Media` varchar(255) NOT NULL,
  `Helpful_Votes` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment`
--

CREATE TABLE `shipment` (
  `Shipment_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Tracking_Num` int(11) NOT NULL,
  `Courier` varchar(255) NOT NULL,
  `Status` varchar(255) NOT NULL,
  `Driver_Start_Timestamp` datetime DEFAULT NULL,
  `Estimated_Delivery_Date` datetime NOT NULL,
  `From_Address` varchar(255) DEFAULT NULL,
  `To_Address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipment`
--

INSERT INTO `shipment` (`Shipment_ID`, `Order_ID`, `Tracking_Num`, `Courier`, `Status`, `Driver_Start_Timestamp`, `Estimated_Delivery_Date`, `From_Address`, `To_Address`) VALUES
(24, 85, 10085, 'In-house Delivery', 'Delivered', '2025-12-11 00:03:29', '2025-12-11 00:05:29', 'Barangay 35, Bacolod', 'Tangub, bacolod 6100'),
(25, 86, 10086, 'In-house Delivery', 'Delivered', '2025-12-11 00:43:53', '2025-12-11 00:45:53', 'Banago, Bacolod', 'Barangay 35, Bacolod City, bacolod 6100'),
(26, 87, 10087, 'In-house Delivery', 'Delivered', '2025-12-11 01:07:25', '2025-12-11 01:09:25', 'Alijis, Bacolod', 'Barangay 3, Bacolod, bacolod 6100'),
(27, 88, 10088, 'In-house Delivery', 'Delivered', '2025-12-11 01:13:37', '2025-12-11 01:15:37', 'Banago, Bacolod', 'Bata, Bacolod, bacolod 6100'),
(28, 89, 10089, 'In-house Delivery', 'Delivered', '2025-12-11 01:26:59', '2025-12-11 01:28:59', 'Bata, Bacolod', 'Tangub, Bacolod city, Bacolod 6100'),
(29, 90, 10090, 'In-house Delivery', 'Delivered', '2025-12-11 01:30:34', '2025-12-11 01:32:34', 'Tangub, Bacolod', 'Alijis, Bacolod, bacolod 6100'),
(30, 91, 10091, 'In-house Delivery', 'Delivered', '2025-12-11 01:37:58', '2025-12-11 01:39:58', 'Bata, Bacolod', 'Banago, Bacolod, Bacolod 6100'),
(31, 92, 10092, 'In-house Delivery', 'Delivered', '2025-12-11 01:42:13', '2025-12-11 01:44:13', 'Tangub, Bacolod', 'Barangay 35, bacolod, bacolod 6100'),
(32, 93, 10093, 'In-house Delivery', 'In Transit', '2025-12-11 01:45:42', '2025-12-11 01:47:42', 'Tangub, Bacolod', 'Estefania, Bacolod, bacolod 6100'),
(33, 94, 10094, 'In-house Delivery', 'In Transit', '2025-12-11 01:51:10', '2025-12-11 01:53:10', 'Bata, Bacolod', 'Banago, Bacolod, bacolod 6100'),
(34, 95, 10095, 'In-house Delivery', 'In Transit', '2025-12-11 01:58:06', '2025-12-11 02:00:06', 'Banago, Bacolod', 'Tangub, Bacolod, bacolod 6100'),
(35, 96, 10096, 'In-house Delivery', 'Delivered', '2025-12-11 02:06:10', '2025-12-11 02:08:10', 'Tangub, Bacolod', 'Bata, Bacolod, bacolod 6100'),
(36, 101, 10101, 'In-house Delivery', 'Delivered', '2025-12-15 09:43:30', '2025-12-15 09:45:30', 'Mandalagan', 'Tangub, Bacolod city, Bacolod 6100'),
(37, 104, 10104, 'In-house Delivery', 'Delivered', '2025-12-15 10:10:13', '2025-12-15 10:12:13', 'Villamonte, Bacolod', 'Celine Homes, Bacolod 6100'),
(38, 105, 10105, 'In-house Delivery', 'In Transit', '2025-12-15 13:24:11', '2025-12-15 13:26:11', 'Warehouse, Bacolod', 'Alijis, Bacolod City, Bacolod 6100'),
(39, 106, 10106, 'In-house Delivery', 'Delivered', '2026-01-02 20:39:37', '2026-01-02 20:41:37', 'Warehouse, Bacolod', 'Pahanocoy, Bacolod 6100'),
(40, 108, 10108, 'In-house Delivery', 'Delivered', '2026-01-02 21:40:38', '2026-01-02 21:42:38', 'Warehouse, Bacolod', 'Bata, Bacolod 6100'),
(41, 107, 10107, 'In-house Delivery', 'Delivered', '2026-01-02 21:43:58', '2026-01-02 21:45:58', 'Cabug, Bacolod', 'Banago, Bacolod 6100');

-- --------------------------------------------------------

--
-- Table structure for table `subscription`
--

CREATE TABLE `subscription` (
  `Subscription_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Type` varchar(255) NOT NULL,
  `Channel` varchar(255) NOT NULL,
  `Status` varchar(255) NOT NULL,
  `Created_At` datetime NOT NULL,
  `Updated_At` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order`
--

CREATE TABLE `tbl_order` (
  `Order_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Customer_Name` varchar(255) DEFAULT NULL,
  `Customer_Email` varchar(255) DEFAULT NULL,
  `Customer_Phone` varchar(50) DEFAULT NULL,
  `Shipping_Address` text DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `Postal_Code` varchar(20) DEFAULT NULL,
  `Payment_Method` varchar(100) DEFAULT NULL,
  `Order_Notes` text DEFAULT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `Shipping` decimal(10,2) DEFAULT NULL,
  `Tax` decimal(10,2) DEFAULT NULL,
  `Order_Date` datetime DEFAULT current_timestamp(),
  `Product_ID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Status` varchar(255) NOT NULL,
  `Total_Amount` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_order`
--

INSERT INTO `tbl_order` (`Order_ID`, `User_ID`, `Customer_Name`, `Customer_Email`, `Customer_Phone`, `Shipping_Address`, `City`, `Postal_Code`, `Payment_Method`, `Order_Notes`, `Subtotal`, `Shipping`, `Tax`, `Order_Date`, `Product_ID`, `Quantity`, `Status`, `Total_Amount`) VALUES
(62, 15, 'Root Admin', 'rootadmin@gmail.com', '+639127361673', 'n/a, Bacolod city 6100', 'Bacolod city', '6100', 'Credit Card', '', 45000.00, 0.00, 0.00, '2025-12-07 11:12:03', 15, 9, 'Delivered', 45000),
(63, 16, 'Jeremiah Roilo', 'jeremiah@gmail.com', '+639127361673', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', 'Bacolod City', '6100', 'Credit Card', '', 4880.00, 0.00, 0.00, '2025-12-07 11:33:01', 15, 1, 'Delivered', 4880),
(64, 16, 'Jeremiah Roilo', 'jeremiah@gmail.com', '+639127361673', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', 'Bacolod City', '6100', 'Cash on Delivery', '', 1350.00, 0.00, 0.00, '2025-12-07 12:39:31', 16, 1, 'Delivered', 1350),
(65, 16, 'Jeremiah Roilo', 'jeremiah@gmail.com', '+639127361673', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', 'Bacolod City', '6100', 'Credit Card', '', 3800.00, 0.00, 0.00, '2025-12-07 13:58:05', 20, 10, 'Delivered', 3800),
(66, 16, 'Jeremiah Roilo', 'jeremiah@gmail.com', '+639127361673', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', 'Bacolod City', '6100', 'Cash on Delivery', '', 1750.00, 0.00, 0.00, '2025-12-07 14:02:21', 23, 5, 'Confirmed', 1750),
(67, 17, 'Seon Soquena', 'seonsoquena4@gmail.com', '+639127361673', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', 'Bacolod City', '6100', 'Cash on Delivery', '', 5250.00, 0.00, 0.00, '2025-12-07 14:05:17', 23, 15, 'Cancelled', 5250),
(68, 17, 'Seon Soquena', 'seonsoquena4@gmail.com', '+639127361673', 'Goldcrest Subdivision, Barangay Bata, Negros Occidental, Bacolod City 6100', 'Bacolod City', '6100', 'Cash on Delivery', '', 499.00, 0.00, 0.00, '2025-12-07 14:09:07', 22, 1, 'Cancelled', 499),
(69, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'Bacolod brgy katilingban, bacolod 6100', 'bacolod', '6100', 'Credit Card', '', 380.00, 0.00, 0.00, '2025-12-10 13:17:09', 20, 1, 'Delivered', 380),
(70, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'bacolod mansilingan, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-10 13:19:43', 20, 1, 'Confirmed', 380),
(71, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'gasdasdas, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 2500.00, 0.00, 0.00, '2025-12-10 19:26:41', 18, 2, 'Confirmed', 2500),
(72, 18, 'rnzo', 'rnzo@gmail.com', '+639939465782', 'Purok katilingban baranggay banago, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 499.00, 0.00, 0.00, '2025-12-10 19:43:59', 22, 1, 'Delivered', 499),
(73, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'dasdasd, asdasdas 6100', 'asdasdas', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-10 20:01:45', 20, 1, 'Shipped', 380),
(74, 18, 'rnzo', 'rnzo@gmail.com', '+63623423432423', 'Katilingban banago, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 499.00, 0.00, 0.00, '2025-12-10 20:18:01', 22, 1, 'Delivered', 499),
(75, 18, 'rnzo', 'rnzo@gmail.com', 'asdasd', 'Banago, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-10 21:36:18', 20, 1, 'Confirmed', 380),
(76, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'Taculing, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-10 21:59:58', 20, 1, 'Confirmed', 380),
(77, 18, 'rnzo', 'rnzo@gmail.com', '+632134321321', 'Banago, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 350.00, 0.00, 0.00, '2025-12-10 22:02:06', 23, 1, 'Confirmed', 350),
(78, 18, 'rnzo', 'rnzo@gmail.com', 'dasdasdas', 'Banago, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 6250.00, 0.00, 0.00, '2025-12-10 22:47:17', 18, 1, 'Confirmed', 6250),
(79, 18, 'rnzo', 'rnzo@gmail.com', 'dasdasdas', 'Banago, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 6250.00, 0.00, 0.00, '2025-12-10 22:47:17', 15, 1, 'Cancelled', 6250),
(80, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'Taculing, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 1250.00, 0.00, 0.00, '2025-12-10 22:59:26', 18, 1, 'Delivered', 1250),
(81, 18, 'rnzo', 'rnzo@gmail.com', 'asdasda', 'Estefania, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-10 23:10:16', 20, 1, 'Confirmed', 380),
(82, 18, 'rnzo', 'rnzo@gmail.com', 'asdasd', 'Banago, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 1250.00, 0.00, 0.00, '2025-12-10 23:22:36', 18, 1, 'Confirmed', 1250),
(83, 18, 'rnzo', 'rnzo@gmail.com', 'dasdasdas', 'Estefania, bacolod 6100', 'bacolod', '6100', 'Credit Card', '', 499.00, 0.00, 0.00, '2025-12-10 23:35:40', 22, 1, 'Delivered', 499),
(85, 18, 'rnzo', 'rnzo@gmail.com', 'fdadasda', 'Tangub, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 899.00, 0.00, 0.00, '2025-12-10 23:56:50', 21, 1, 'Delivered', 899),
(86, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'Barangay 35, Bacolod City, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 1250.00, 0.00, 0.00, '2025-12-11 00:43:05', 18, 1, 'Delivered', 1250),
(87, 18, 'rnzo', 'rnzo@gmail.com', 'dfsdfsd', 'Barangay 3, Bacolod, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 499.00, 0.00, 0.00, '2025-12-11 01:06:19', 22, 1, 'Delivered', 499),
(88, 18, 'rnzo', 'rnzo@gmail.com', 'sadasdas', 'Bata, Bacolod, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 1250.00, 0.00, 0.00, '2025-12-11 01:12:48', 18, 1, 'Delivered', 1250),
(89, 18, 'rnzo', 'rnzo@gmail.com', 'asdasdasdsa', 'Tangub, Bacolod city, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 1250.00, 0.00, 0.00, '2025-12-11 01:20:48', 18, 1, 'Delivered', 1250),
(90, 18, 'rnzo', 'rnzo@gmail.com', 'asdasdasd', 'Alijis, Bacolod, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 1250.00, 0.00, 0.00, '2025-12-11 01:29:44', 18, 1, 'Delivered', 1250),
(91, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'Banago, Bacolod, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 1250.00, 0.00, 0.00, '2025-12-11 01:37:33', 18, 1, 'Delivered', 1250),
(92, 18, 'rnzo', 'rnzo@gmail.com', '+636234234324', 'Barangay 35, bacolod, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 499.00, 0.00, 0.00, '2025-12-11 01:41:38', 22, 1, 'Delivered', 499),
(93, 18, 'rnzo', 'rnzo@gmail.com', 'asdasdasdsa', 'Estefania, Bacolod, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 1080.00, 0.00, 0.00, '2025-12-11 01:45:10', 17, 1, 'Shipped', 1080),
(94, 18, 'rnzo', 'rnzo@gmail.com', 'dasdasdasd', 'Banago, Bacolod, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 350.00, 0.00, 0.00, '2025-12-11 01:50:26', 23, 1, 'Shipped', 350),
(95, 18, 'rnzo', 'rnzo@gmail.com', 'asdasdasdsa', 'Tangub, Bacolod, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 899.00, 0.00, 0.00, '2025-12-11 01:57:26', 21, 1, 'Shipped', 899),
(96, 18, 'rnzo', 'rnzo@gmail.com', 'asdasdasda', 'Bata, Bacolod, bacolod 6100', 'bacolod', '6100', 'Cash on Delivery', '', 499.00, 0.00, 0.00, '2025-12-11 02:05:28', 22, 1, 'Delivered', 499),
(97, 18, 'rnzo', 'rnzo@gmail.com', '+639909233242', 'Bata, purok bayabasan, Bacolod city, Bacolod 6100', 'Bacolod', '6100', 'Credit Card', '', 23750.00, 0.00, 0.00, '2025-12-15 09:21:31', 18, 19, 'Cancelled', 23750),
(98, 18, 'rnzo', 'rnzo@gmail.com', '+639909233242', 'Marapara 1, Brgy Bata, Bacolod city, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 25000.00, 0.00, 0.00, '2025-12-15 09:38:08', 18, 20, 'Cancelled', 25000),
(99, 18, 'rnzo', 'rnzo@gmail.com', '+639909233242', 'Baranggay Bata, Marapara 1, Bacolod City, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-15 09:39:31', 20, 1, 'Cancelled', 380),
(100, 18, 'rnzo', 'rnzo@gmail.com', '+639909233242', 'Baranggay 39, Bacolod City, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 1900.00, 0.00, 0.00, '2025-12-15 09:41:33', 20, 5, 'Cancelled', 1900),
(101, 18, 'rnzo', 'rnzo@gmail.com', '+639909233242', 'Tangub, Bacolod city, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-15 09:42:44', 20, 1, 'Delivered', 380),
(102, 18, 'rnzo', 'rnzo@gmail.com', '+639909233242', 'Barangay 39, Bacolod city, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-15 09:45:21', 20, 1, 'Cancelled', 380),
(103, 18, 'rnzo', 'rnzo@gmail.com', '+639909233242', 'Barangay 1, Bacolod city, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 380.00, 0.00, 0.00, '2025-12-15 09:57:33', 20, 1, 'Cancelled', 380),
(104, 18, 'rnzo', 'rnzo@gmail.com', '+639909233242', 'Celine Homes, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 9500.00, 0.00, 0.00, '2025-12-15 10:09:18', 20, 25, 'Delivered', 9500),
(105, 18, 'Rnzo Doromal', 'rnzo@gmail.com', '+639909233242', 'Alijis, Bacolod City, Bacolod 6100', 'Bacolod', '6100', 'Cash on Delivery', '', 2790.00, 0.00, 0.00, '2025-12-15 13:23:08', 20, 8, 'Shipped', 2790),
(106, 20, 'seon juval', 'seon@gmail.com', '+639909233242', 'Pahanocoy, Bacolod 6100', 'Bacolod', '6100', 'Credit Card', '', 2160.00, 0.00, 0.00, '2026-01-02 20:37:44', 17, 2, 'Delivered', 2160),
(107, 20, 'seon juval', 'seon@gmail.com', '+639909233242', 'Banago, Bacolod 6100', 'Bacolod', '6100', 'Credit Card', '', 6487.00, 0.00, 0.00, '2026-01-02 21:38:12', 22, 13, 'Delivered', 6487),
(108, 20, 'seon juval', 'seon@gmail.com', '+639909233242', 'Bata, Bacolod 6100', 'Bacolod', '6100', 'Credit Card', '', 1080.00, 0.00, 0.00, '2026-01-02 21:39:26', 17, 1, 'Delivered', 1080);

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE `ticket` (
  `Ticket_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Subject` varchar(255) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `Status` varchar(255) NOT NULL,
  `CreatedDate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_ID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Phone` int(11) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Profile_Picture` varchar(255) DEFAULT NULL,
  `Role` varchar(255) NOT NULL,
  `user_secret_key` varchar(255) DEFAULT NULL,
  `last_2fa_verification` datetime DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `Name`, `Email`, `Password`, `Phone`, `Address`, `Profile_Picture`, `Role`, `user_secret_key`, `last_2fa_verification`, `google_id`, `Status`) VALUES
(15, 'Root Admin', 'rootadmin@gmail.com', '$2y$10$EsqCawZkbNiWgWh/psRq1.BUCwI3NQu17gRsboe06A/6khmQr7R7S', NULL, NULL, NULL, 'Admin', NULL, NULL, NULL, 'Active'),
(16, 'Jeremiah Roilo', 'jeremiah@gmail.com', '$2y$10$dd3bhu5POnP52yeXB/bshO1m8YG9IWQlSiuiMkX6SfdqQz1Vmj3Ye', NULL, NULL, NULL, 'customer', 'PJGEBZ3BZ2HH3HDK', '2025-12-07 09:05:54', NULL, 'Active'),
(17, 'Seon Soquena', 'seonsoquena4@gmail.com', '$2y$10$paMWK0iafaxs5R.oSBC/1.SG070vKnXYbKZyEoHz9bO5fjZZF7RPe', NULL, NULL, NULL, 'customer', 'WYBBYF4VPBLL3IKX', '2025-12-07 14:04:35', NULL, 'Active'),
(19, 'Yuan Ajita', 'yuan@gmail.com', '$2y$10$QhE5VUTs/V49BLQ04F/z2.0gKnx2cxksgmRPSkfTmwTW3ZyEmvSZS', NULL, NULL, NULL, 'customer', 'XCDQ4TASPKA2ZTID', '2025-12-15 13:14:27', NULL, 'Active'),
(20, 'seon juval', 'seon@gmail.com', '$2y$10$SEV3EQJF983sFs1fvZ5gge5Q24aR.fJ1dLUZjSV6eAbvrpWauZGPW', NULL, NULL, NULL, 'customer', '5XNFUWZ4IRVZRRZX', '2026-01-02 20:33:08', NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `user_coupons`
--

CREATE TABLE `user_coupons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `claimed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user_coupons`
--

INSERT INTO `user_coupons` (`id`, `user_id`, `coupon_id`, `claimed_at`) VALUES
(1, 8, 2, '2025-10-24 11:41:04'),
(2, 8, 1, '2025-10-24 11:41:05'),
(3, 8, 3, '2025-10-24 12:04:50'),
(4, 8, 20, '2025-10-30 22:25:45'),
(5, 8, 23, '2025-10-30 22:56:00'),
(6, 8, 27, '2025-10-30 23:06:26'),
(7, 10, 30, '2025-11-27 16:12:13'),
(8, 13, 32, '2025-12-03 23:59:26'),
(9, 16, 33, '2025-12-07 11:30:51'),
(10, 18, 39, '2025-12-15 10:06:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`Log_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `analytics_report`
--
ALTER TABLE `analytics_report`
  ADD PRIMARY KEY (`Report_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`Cart_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD PRIMARY KEY (`Cart_Item_ID`),
  ADD KEY `Cart_ID` (`Cart_ID`,`Product_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`Category_ID`);

--
-- Indexes for table `coupon`
--
ALTER TABLE `coupon`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `code_2` (`code`);

--
-- Indexes for table `loyalty`
--
ALTER TABLE `loyalty`
  ADD PRIMARY KEY (`Loyalty_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`Notification_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`Order_Detail_ID`),
  ADD KEY `Product_ID` (`Product_ID`),
  ADD KEY `Order_ID` (`Order_ID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Payment_ID`),
  ADD KEY `Order_ID` (`Order_ID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`Product_ID`),
  ADD KEY `Category_ID` (`Category_ID`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`Review_ID`),
  ADD KEY `User_ID` (`User_ID`,`Product_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Indexes for table `shipment`
--
ALTER TABLE `shipment`
  ADD PRIMARY KEY (`Shipment_ID`),
  ADD KEY `Order_ID` (`Order_ID`);

--
-- Indexes for table `subscription`
--
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`Subscription_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `tbl_order`
--
ALTER TABLE `tbl_order`
  ADD PRIMARY KEY (`Order_ID`),
  ADD KEY `User_ID` (`User_ID`,`Product_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Indexes for table `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`Ticket_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_ID`);

--
-- Indexes for table `user_coupons`
--
ALTER TABLE `user_coupons`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `Log_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `analytics_report`
--
ALTER TABLE `analytics_report`
  MODIFY `Report_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `Cart_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_item`
--
ALTER TABLE `cart_item`
  MODIFY `Cart_Item_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `Category_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coupon`
--
ALTER TABLE `coupon`
  MODIFY `promo_id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `loyalty`
--
ALTER TABLE `loyalty`
  MODIFY `Loyalty_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `Notification_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `Order_Detail_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `Product_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `Review_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment`
--
ALTER TABLE `shipment`
  MODIFY `Shipment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `subscription`
--
ALTER TABLE `subscription`
  MODIFY `Subscription_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_order`
--
ALTER TABLE `tbl_order`
  MODIFY `Order_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `ticket`
--
ALTER TABLE `ticket`
  MODIFY `Ticket_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_coupons`
--
ALTER TABLE `user_coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `analytics_report`
--
ALTER TABLE `analytics_report`
  ADD CONSTRAINT `analytics_report_ibfk_1` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD CONSTRAINT `cart_item_ibfk_1` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_item_ibfk_2` FOREIGN KEY (`Cart_ID`) REFERENCES `cart` (`Cart_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `loyalty`
--
ALTER TABLE `loyalty`
  ADD CONSTRAINT `loyalty_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`Order_ID`) REFERENCES `tbl_order` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `tbl_order` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`Category_ID`) REFERENCES `category` (`Category_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shipment`
--
ALTER TABLE `shipment`
  ADD CONSTRAINT `shipment_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `tbl_order` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subscription`
--
ALTER TABLE `subscription`
  ADD CONSTRAINT `subscription_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_order`
--
ALTER TABLE `tbl_order`
  ADD CONSTRAINT `tbl_order_ibfk_1` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;