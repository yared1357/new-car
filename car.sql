CREATE DATABASE IF NOT EXISTS `car`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `car`;

CREATE TABLE IF NOT EXISTS `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `images` longtext DEFAULT '[]',
  `category` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `cars`
  (`id`, `make`, `model`, `year`, `price`, `mileage`, `fuel_type`, `transmission`, `image_url`, `featured`, `created_at`, `discount_percentage`, `old_price`, `description`, `images`, `category`)
VALUES
  (4, 'Toyota', 'Camry', 2022, 1333333.34, 3000, 'Diesel', 'Automatic', '', 0, '2025-12-23 02:20:37', 12.00, 1515151.52, 'The Toyota Camry combines reliability with modern style. Featuring a spacious interior, advanced safety features, and a fuel-efficient engine, the Camry is perfect for families and commuters alike. Enjoy a smooth ride with intuitive technology at your fingertips.', '["uploads/car_694a6c703189b.jpeg","uploads/car_694a6c7031b6d.jpeg"]', 'New'),
  (5, 'Ford', 'F-150', 2023, 1272727.27, 12000, 'Electric', 'Automatic', '', 0, '2025-12-23 02:20:23', 10.00, 1414141.41, 'The Ford F-150 is America''s best-selling truck for a reason. With robust towing capacity, a variety of powerful engines, and a suite of tech features, it''s built to tackle any job. The spacious interior and advanced safety systems make it a great choice for work or play.', '["uploads/car_694a6ce77aa8f.jpeg","uploads/car_694a6ce77af33.jpeg","uploads/car_694a6ce77b20e.jpeg","uploads/car_694a6ce77b533.jpeg"]', NULL),
  (6, 'BMW', '3 Series', 2025, 1900000.00, 2400, 'Hybrid', 'Manual', '', 1, '2025-12-23 02:22:25', 5.00, 2000000.00, 'The BMW 3 Series stands out with its luxurious interior and sporty performance. With precise handling, turbocharged power, and cutting-edge technology, this sedan offers an exhilarating driving experience. Perfect for those who desire both sophistication and speed.', '["uploads/car_694a6d6192dda.jpeg","uploads/car_694a6d6193130.jpeg","uploads/car_694a6d61932ff.jpeg","uploads/car_694a6d6193520.jpeg"]', 'Secondhand'),
  (7, 'Mercedes-Benz', 'C-Class', 2020, 1877551.02, 2500, 'Electric', 'Automatic', '', 0, '2025-12-23 02:23:40', 20.00, 2346938.78, 'The Mercedes-Benz C-Class offers a perfect blend of luxury and performance. With its refined interior, advanced safety features, and powerful engine options, it promises a comfortable and exhilarating ride. Ideal for those who appreciate elegance on the road.', '["uploads/car_694a6dac3e03f.jpeg","uploads/car_694a6dac3e288.jpeg","uploads/car_694a6dac3e4d0.jpeg"]', NULL);

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `phone` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `contact_messages`
  (`id`, `name`, `email`, `message`, `submitted_at`, `is_read`, `phone`)
VALUES
  (3, 'Yared', NULL, 'Hi', '2026-02-09 08:17:05', 1, '0926862255'),
  (4, 'Yaya', 'yaya@gmail.com', 'Good afternoon.', '2026-02-11 02:03:41', 0, '0926862211');

CREATE TABLE IF NOT EXISTS `hero_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `image_path` varchar(500) NOT NULL,
  `order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `hero_images`
  (`id`, `title`, `description`, `image_path`, `order`, `created_at`, `updated_at`)
VALUES
  (6, '15% Discount From All Cars', '', 'admin/images/hero_694a6aefeedc2.jpeg', 0, '2025-12-23 10:11:59', '2025-12-23 10:11:59'),
  (7, 'Find Your Perfect Ride Today!', '', 'admin/images/hero_694a6b142dd88.jpeg', 0, '2025-12-23 10:12:36', '2025-12-23 10:12:36'),
  (9, 'Explore Our Exclusive Car Collection!', '', 'admin/images/hero_694a6b83eccd6.jpeg', 0, '2025-12-23 10:14:27', '2025-12-23 10:14:27');

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','user') DEFAULT 'admin',
  `created_at` datetime DEFAULT current_timestamp(),
  `email` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `users`
  (`id`, `username`, `password`, `role`, `created_at`, `email`, `name`, `phone`)
VALUES
  (1, 'yared', '$2y$10$PUmUYrsPAhbGYziL6T26auOAdpPHkkgyIk0/rPsIMnKzBSUVlAKFK', 'super_admin', '2025-12-22 11:02:23', 'yared@gmail.com', 'Yared Alebachewu', '0926862255'),
  (2, 'guyatu', '$2y$10$PUmUYrsPAhbGYziL6T26auOAdpPHkkgyIk0/rPsIMnKzBSUVlAKFK', 'admin', '2025-12-22 12:19:36', 'guyatu@gmail.com', 'Guyatu Gelgelo', '0963133317');

ALTER TABLE `users`
  MODIFY `role` enum('super_admin','admin','user') DEFAULT 'admin';
UPDATE `users` SET `role` = 'super_admin' WHERE `phone` = '0926862255';

CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES
  ('site_name', 'Smart Car Selling'),
  ('tagline', 'Premium Used Cars'),
  ('logo_path', 'car.png'),
  ('contact_phone', ''),
  ('contact_email', ''),
  ('address', ''),
  ('primary_color', '#1E40AF'),
  ('accent_color', '#F59E0B'),
  ('header_color', '#F3F4F6'),
  ('footer_color', '#172554'),

CREATE TABLE IF NOT EXISTS `social_links` (
  `platform` varchar(32) NOT NULL,
  `url` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `social_links` (`platform`, `url`)
SELECT REPLACE(`setting_key`, 'social_', ''), `setting_value`
FROM `site_settings`
WHERE `setting_key` IN ('social_x', 'social_facebook', 'social_linkedin');

INSERT IGNORE INTO `social_links` (`platform`, `url`) VALUES
  ('facebook', ''),
  ('tiktok', ''),
  ('telegram', '');

DELETE FROM `social_links`
WHERE `platform` IN ('x', 'linkedin');

DELETE FROM `site_settings`
WHERE `setting_key` IN ('social_x', 'social_facebook', 'social_linkedin');
