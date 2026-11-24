-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.byetcluster.com
-- Generation Time: Mar 12, 2026 at 08:19 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40634592_smart_car`
--

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `mileage` int(11) NOT NULL,
  `fuel_type` enum('Petrol','Diesel','Electric','Hybrid') NOT NULL,
  `transmission` enum('Manual','Automatic') NOT NULL,
  `image_url` text NOT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `old_price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]'
) ;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `make`, `model`, `year`, `price`, `mileage`, `fuel_type`, `transmission`, `image_url`, `featured`, `created_at`, `discount_percentage`, `old_price`, `description`, `images`, `category`) VALUES
(4, 'Toyota', 'Camry', 2022, '1333333.34', 3000, 'Diesel', 'Automatic', '', 0, '2025-12-23 02:20:37', '12.00', '1515151.52', 'The Toyota Camry combines reliability with modern style. Featuring a spacious interior, advanced safety features, and a fuel-efficient engine, the Camry is perfect for families and commuters alike. Enjoy a smooth ride with intuitive technology at your fingertips.', '[\"uploads\\/car_694a6c703189b.jpeg\",\"uploads\\/car_694a6c7031b6d.jpeg\"]', 'New'),
(5, 'Ford', 'F-150', 2023, '1272727.27', 12000, 'Electric', 'Automatic', '', 0, '2025-12-23 02:20:23', '10.00', '1414141.41', 'The Ford F-150 is Americaâ€™s best-selling truck for a reason. With robust towing capacity, a variety of powerful engines, and a suite of tech features, itâ€™s built to tackle any job. The spacious interior and advanced safety systems make it a great choice for work or play.', '[\"uploads\\/car_694a6ce77aa8f.jpeg\",\"uploads\\/car_694a6ce77af33.jpeg\",\"uploads\\/car_694a6ce77b20e.jpeg\",\"uploads\\/car_694a6ce77b533.jpeg\"]', NULL),
(6, 'BMW', '3 Series', 2025, '1900000.00', 2400, 'Hybrid', 'Manual', '', 1, '2025-12-23 02:22:25', '5.00', '2000000.00', 'The BMW 3 Series stands out with its luxurious interior and sporty performance. With precise handling, turbocharged power, and cutting-edge technology, this sedan offers an exhilarating driving experience. Perfect for those who desire both sophistication and speed.', '[\"uploads\\/car_694a6d6192dda.jpeg\",\"uploads\\/car_694a6d6193130.jpeg\",\"uploads\\/car_694a6d61932ff.jpeg\",\"uploads\\/car_694a6d6193520.jpeg\"]', 'Secondhand'),
(7, 'Mercedes-Benz', 'C-Class', 2020, '1877551.02', 2500, 'Electric', 'Automatic', '', 0, '2025-12-23 02:23:40', '20.00', '2346938.78', 'The Mercedes-Benz C-Class offers a perfect blend of luxury and performance. With its refined interior, advanced safety features, and powerful engine options, it promises a comfortable and exhilarating ride. Ideal for those who appreciate elegance on the road.', '[\"uploads\\/car_694a6dac3e03f.jpeg\",\"uploads\\/car_694a6dac3e288.jpeg\",\"uploads\\/car_694a6dac3e4d0.jpeg\"]', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `phone` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `submitted_at`, `is_read`, `phone`) VALUES
(3, 'Yared', NULL, 'Hi', '2026-02-09 08:17:05', 1, '0926862255'),
(4, 'Yaya', 'yaya@gmail.com', 'Good afternoon.', '2026-02-11 02:03:41', 0, '0926862211');

-- --------------------------------------------------------

--
-- Table structure for table `hero_images`
--

CREATE TABLE `hero_images` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(500) NOT NULL,
  `order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hero_images`
--

INSERT INTO `hero_images` (`id`, `title`, `description`, `image_path`, `order`, `created_at`, `updated_at`) VALUES
(6, '15% Discount From All Cars', '', 'admin/images/hero_694a6aefeedc2.jpeg', 0, '2025-12-23 10:11:59', '2025-12-23 10:11:59'),
(7, 'Find Your Perfect Ride Today!', '', 'admin/images/hero_694a6b142dd88.jpeg', 0, '2025-12-23 10:12:36', '2025-12-23 10:12:36'),
(9, 'Explore Our Exclusive Car Collection!', '', 'admin/images/hero_694a6b83eccd6.jpeg', 0, '2025-12-23 10:14:27', '2025-12-23 10:14:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'admin',
  `created_at` datetime DEFAULT current_timestamp(),
  `email` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `email`, `name`, `phone`) VALUES
(1, 'yared', '$2y$10$PUmUYrsPAhbGYziL6T26auOAdpPHkkgyIk0/rPsIMnKzBSUVlAKFK', 'admin', '2025-12-22 11:02:23', 'yared@gmail.com', 'Yared Alebachewu', '0926862255'),
(2, 'guyatu', '$2y$10$PUmUYrsPAhbGYziL6T26auOAdpPHkkgyIk0/rPsIMnKzBSUVlAKFK', 'admin', '2025-12-22 12:19:36', 'guyatu@gmail.com', 'Guyatu Gelgelo', '0963133317');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_images`
--
ALTER TABLE `hero_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hero_images`
--
ALTER TABLE `hero_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
