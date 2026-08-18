-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 20, 2025 at 12:53 PM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bms`
--

-- --------------------------------------------------------

--
-- Table structure for table `category_list`
--

CREATE TABLE `category_list` (
  `category_id` int(30) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `category_list`
--

INSERT INTO `category_list` (`category_id`, `name`, `description`, `status`, `delete_flag`, `date_created`, `date_updated`) VALUES
(1, 'Cakes', 'List of cake items', 1, 0, '2025-02-14 09:16:23', '2025-04-18 14:39:32'),
(13, 'Chocolates', 'List of chocolate items', 1, 0, '2025-01-10 14:38:56', '2025-04-18 14:39:40'),
(18, 'Biscuits', 'List of Biscuits items', 1, 0, '2025-02-20 17:13:05', '2025-04-18 14:38:14'),
(20, 'Bread', '', 1, 0, '2025-04-17 22:51:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_list`
--

CREATE TABLE `product_list` (
  `product_id` int(30) NOT NULL,
  `product_code` text NOT NULL,
  `category_id` int(30) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `price` double NOT NULL DEFAULT 0,
  `stock` double NOT NULL DEFAULT 0,
  `image` varchar(222) NOT NULL,
  `alert_restock` double NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `product_list`
--

INSERT INTO `product_list` (`product_id`, `product_code`, `category_id`, `name`, `description`, `price`, `stock`, `image`, `alert_restock`, `status`, `delete_flag`, `date_created`, `date_updated`) VALUES
(42, '8887870', 1, 'Chocolate Cake Fruit Topping ', 'Chocolate Cake Fruit Topping: Indulge in the Delightful Chocolate Cake Fruit Topping.', 300, 30, 'cake.png', 8, 1, 0, '2025-04-16 18:09:41', '2025-04-17 22:09:34'),
(43, '9999999', 13, 'Browni', ' Choco Chip Brownie is a gooey chocolate brownie loaded with dark chocolate chips.', 100, 40, 'browni.png', 7, 0, 0, '2025-04-16 18:10:35', '2025-04-17 22:09:49'),
(44, '555555', 13, 'Mars', 'MARS® Chocolate Bars are an irresistible blend of chocolate, caramel and nougat.', 700, 25, 'mars.jpg', 8, 1, 0, '2025-04-16 18:11:06', '2025-04-17 22:13:25'),
(45, '1212112', 18, 'Cookies', 'Cookies are a delightful blend of digital memory and user convenience — a sweet combo of saved preferences, smooth logins, and personalized experiences. ', 30, 50, 'cooki.png', 9, 1, 0, '2025-04-01 20:45:07', '2025-04-18 14:39:58');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_items`
--

CREATE TABLE `transaction_items` (
  `transaction_id` int(30) NOT NULL,
  `product_id` int(30) NOT NULL,
  `quantity` double NOT NULL DEFAULT 0,
  `price` double NOT NULL DEFAULT 0,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `transaction_items`
--

INSERT INTO `transaction_items` (`transaction_id`, `product_id`, `quantity`, `price`, `date_added`) VALUES
(61, 43, 2, 100, '2025-04-18 11:13:55'),
(61, 42, 1, 300, '2025-04-18 11:13:55'),
(62, 42, 1, 300, '2025-04-18 11:33:48'),
(66, 42, 1, 300, '2025-05-15 13:13:21');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_list`
--

CREATE TABLE `transaction_list` (
  `transaction_id` int(30) NOT NULL,
  `receipt_no` text NOT NULL,
  `sub_total` double NOT NULL DEFAULT 0,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_amount` double NOT NULL DEFAULT 0,
  `discount_percent` double NOT NULL DEFAULT 0,
  `total` double NOT NULL DEFAULT 0,
  `tendered_amount` double NOT NULL DEFAULT 0,
  `change` double NOT NULL DEFAULT 0,
  `user_id` int(30) DEFAULT 1,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `transaction_list`
--
-- These rows predate the sub_total/discount_* columns, so no discount data
-- was ever recorded for them. They are backfilled here with sub_total =
-- total (payable) and zero discount, which reflects the only information
-- actually available (no discount was tracked at the time these were made).

INSERT INTO `transaction_list` (`transaction_id`, `receipt_no`, `sub_total`, `discount_type`, `discount_amount`, `discount_percent`, `total`, `tendered_amount`, `change`, `user_id`, `date_added`) VALUES
(1, '1644804759', 205, 'percent', 0, 0, 205, 300, 95, NULL, '2025-02-14 02:12:39'),
(2, '1644804881', 1000, 'percent', 0, 0, 1000, 1000, 0, NULL, '2025-02-14 02:14:41'),
(5, '1644807473', 85, 'percent', 0, 0, 85, 100, 15, NULL, '2025-02-14 02:57:53'),
(6, '1677890444', 10, 'percent', 0, 0, 10, 20, 10, NULL, '2025-03-04 00:40:44'),
(61, '1744974835', 500, 'percent', 0, 0, 500, 1000, 500, 101, '2025-04-18 11:13:55'),
(62, '1744976028', 300, 'percent', 0, 0, 300, 500, 200, 101, '2025-04-18 11:33:48'),
(66, '1747314801', 300, 'percent', 0, 0, 300, 1000, 700, 101, '2025-05-15 13:13:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_list`
--

CREATE TABLE `user_list` (
  `user_id` int(30) NOT NULL,
  `email` text NOT NULL,
  `username` text NOT NULL,
  `password` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_list`
--

INSERT INTO `user_list` (`user_id`, `email`, `username`, `password`, `status`, `date_created`) VALUES
(101, 'sujankarki230@icloud.com', 'red', 'red123', 1, '2025-03-29 11:01:27'),
(107, 'karki@gamil.com', 'sujan', 'sujan', 1, '2025-04-18 05:00:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category_list`
--
ALTER TABLE `category_list`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `product_list`
--
ALTER TABLE `product_list`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `product_list_ibfk_1` (`category_id`);

--
-- Indexes for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD KEY `product_id` (`product_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `transaction_list`
--
ALTER TABLE `transaction_list`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_list`
--
ALTER TABLE `user_list`
  ADD PRIMARY KEY (`user_id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category_list`
--
ALTER TABLE `category_list`
  MODIFY `category_id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_list`
--
ALTER TABLE `product_list`
  MODIFY `product_id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `transaction_list`
--
ALTER TABLE `transaction_list`
  MODIFY `transaction_id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `user_list`
--
ALTER TABLE `user_list`
  MODIFY `user_id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product_list`
--
ALTER TABLE `product_list`
  ADD CONSTRAINT `product_list_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category_list` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product_list` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transaction_list` (`transaction_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_list`
--
ALTER TABLE `transaction_list`
  ADD CONSTRAINT `transaction_list_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_list` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;