-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 31, 2026 at 01:44 PM
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
-- Database: `budget_accounting`
--

-- --------------------------------------------------------

--
-- Table structure for table `auto_analytical_rules`
--

CREATE TABLE `auto_analytical_rules` (
  `id` int(11) NOT NULL,
  `rule_type` enum('product','category') NOT NULL,
  `rule_value` varchar(150) NOT NULL,
  `cost_center_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auto_analytical_rules`
--

INSERT INTO `auto_analytical_rules` (`id`, `rule_type`, `rule_value`, `cost_center_id`, `created_at`) VALUES
(1, 'category', 'Raw Materials', 1, '2026-01-31 07:52:40'),
(2, 'category', 'Furniture', 1, '2026-01-31 07:52:40'),
(3, 'category', 'Accessories', 4, '2026-01-31 07:52:40'),
(4, 'product', '1', 1, '2026-01-31 07:52:40'),
(5, 'product', '9', 5, '2026-01-31 07:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `cost_center_id` int(11) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','revised','closed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `cost_center_id`, `amount`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(2, 2, 150000.00, '2026-01-01', '2026-03-31', 'active', '2026-01-31 07:52:40'),
(3, 3, 200000.00, '2026-01-01', '2026-03-31', 'active', '2026-01-31 07:52:40'),
(4, 4, 300000.00, '2026-01-01', '2026-03-31', 'active', '2026-01-31 07:52:40'),
(6, 5, 500000.00, '2026-01-01', '2026-01-31', 'active', '2026-01-31 08:46:18'),
(7, 5, 10000.00, '2026-01-01', '2026-01-31', 'active', '2026-01-31 08:52:47'),
(8, 5, 200000.00, '2026-01-01', '2026-03-31', 'active', '2026-01-31 08:54:01');

-- --------------------------------------------------------

--
-- Table structure for table `budget_revisions`
--

CREATE TABLE `budget_revisions` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `old_amount` decimal(14,2) DEFAULT NULL,
  `new_amount` decimal(14,2) DEFAULT NULL,
  `revised_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_revisions`
--

INSERT INTO `budget_revisions` (`id`, `budget_id`, `old_amount`, `new_amount`, `revised_at`) VALUES
(2, 7, 100000.00, 10000.00, '2026-01-31 11:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('customer','vendor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `type`, `created_at`) VALUES
(1, 'Acme Suppliers', 'vendor', '2026-01-31 07:52:40'),
(2, 'Global Materials Ltd', 'vendor', '2026-01-31 07:52:40'),
(3, 'Tech Solutions Inc', 'vendor', '2026-01-31 07:52:40'),
(4, 'Furniture World', 'customer', '2026-01-31 07:52:40'),
(5, 'Home Decor Hub', 'customer', '2026-01-31 07:52:40'),
(6, 'Office Interiors', 'customer', '2026-01-31 07:52:40'),
(7, 'Yusuf Gundarwala', 'vendor', '2026-01-31 07:56:15');

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--

CREATE TABLE `cost_centers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cost_centers`
--

INSERT INTO `cost_centers` (`id`, `name`, `created_at`) VALUES
(1, 'Manufacturing', '2026-01-31 07:52:40'),
(2, 'Marketing', '2026-01-31 07:52:40'),
(3, 'R&D', '2026-01-31 07:52:40'),
(4, 'Operations', '2026-01-31 07:52:40'),
(5, 'IT Infrastructure', '2026-01-31 07:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `doc_type` enum('PO','SO','VendorBill','CustomerInvoice') NOT NULL,
  `contact_id` int(11) NOT NULL,
  `cost_center_id` int(11) DEFAULT NULL,
  `total_amount` decimal(14,2) DEFAULT 0.00,
  `status` enum('draft','posted','cancelled') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `doc_type`, `contact_id`, `cost_center_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 'VendorBill', 1, 1, 75000.00, 'posted', '2026-01-31 07:52:40'),
(2, 'VendorBill', 3, 5, 125000.00, 'posted', '2026-01-31 07:52:40'),
(3, 'CustomerInvoice', 5, 1, 82000.00, 'posted', '2026-01-31 07:52:40'),
(4, 'CustomerInvoice', 5, 1, 45000.00, 'posted', '2026-01-31 07:52:40'),
(5, 'VendorBill', 2, 2, 57000.00, 'posted', '2026-01-31 07:52:40'),
(6, 'PO', 7, NULL, 136000.00, 'posted', '2026-01-31 08:49:12');

-- --------------------------------------------------------

--
-- Table structure for table `document_lines`
--

CREATE TABLE `document_lines` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(14,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_lines`
--

INSERT INTO `document_lines` (`id`, `document_id`, `product_id`, `quantity`, `price`, `line_total`) VALUES
(1, 1, 1, 20, 2500.00, 50000.00),
(2, 1, 2, 50, 450.00, 22500.00),
(3, 1, 10, 3, 833.33, 2500.00),
(4, 2, 9, 20, 1200.00, 24000.00),
(5, 2, 10, 125, 800.00, 100000.00),
(6, 3, 4, 2, 25000.00, 50000.00),
(7, 3, 5, 2, 12000.00, 24000.00),
(8, 3, 8, 1, 5500.00, 5500.00),
(9, 3, 10, 3, 833.33, 2500.00),
(10, 4, 6, 1, 45000.00, 45000.00),
(11, 5, 8, 10, 5700.00, 57000.00),
(12, 6, 3, 40, 3200.00, 128000.00),
(13, 6, 10, 10, 800.00, 8000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `paid_amount` decimal(14,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `razorpay_payment_id` varchar(120) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `document_id`, `paid_amount`, `payment_method`, `razorpay_payment_id`, `payment_date`, `created_at`) VALUES
(1, 1, 75000.00, 'bank_transfer', 'pay_demo_001', '2026-01-31', '2026-01-31 07:52:41'),
(2, 4, 20000.00, 'card', 'pay_demo_002', '2026-01-31', '2026-01-31 07:52:41');

-- --------------------------------------------------------

--
-- Table structure for table `portal_access`
--

CREATE TABLE `portal_access` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portal_access`
--

INSERT INTO `portal_access` (`id`, `user_id`, `contact_id`) VALUES
(1, 2, 5);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(14,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `created_at`) VALUES
(1, 'Oak Wood Panels', 'Raw Materials', 2500.00, '2026-01-31 07:52:40'),
(2, 'Metal Fasteners Pack', 'Raw Materials', 450.00, '2026-01-31 07:52:40'),
(3, 'Premium Fabric Roll', 'Raw Materials', 3200.00, '2026-01-31 07:52:40'),
(4, 'Executive Desk', 'Furniture', 25000.00, '2026-01-31 07:52:40'),
(5, 'Ergonomic Chair', 'Furniture', 12000.00, '2026-01-31 07:52:40'),
(6, 'Conference Table', 'Furniture', 45000.00, '2026-01-31 07:52:40'),
(7, 'Storage Cabinet', 'Furniture', 8500.00, '2026-01-31 07:52:40'),
(8, 'Office Partition', 'Accessories', 5500.00, '2026-01-31 07:52:40'),
(9, 'LED Desk Lamp', 'Accessories', 1200.00, '2026-01-31 07:52:40'),
(10, 'Cable Management Kit', 'Accessories', 800.00, '2026-01-31 07:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `cost_center_id` int(11) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `document_id`, `cost_center_id`, `amount`, `transaction_date`, `created_at`) VALUES
(1, 1, 1, 72500.00, '2026-01-31', '2026-01-31 07:52:40'),
(2, 1, 4, 2500.00, '2026-01-31', '2026-01-31 07:52:40'),
(3, 2, 5, 125000.00, '2026-01-31', '2026-01-31 07:52:40'),
(4, 5, 2, 35000.00, '2026-01-26', '2026-01-31 07:52:41'),
(5, 5, 2, 22000.00, '2026-01-21', '2026-01-31 07:52:41'),
(6, 6, 1, 128000.00, '2026-01-31', '2026-01-31 08:49:36'),
(7, 6, 4, 8000.00, '2026-01-31', '2026-01-31 08:49:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `role` enum('admin','portal') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@demo.com', 'admin', '2026-01-31 07:52:40'),
(2, 'Portal User', 'customer@demo.com', 'portal', '2026-01-31 07:52:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auto_analytical_rules`
--
ALTER TABLE `auto_analytical_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cost_center_id` (`cost_center_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cost_center_id` (`cost_center_id`);

--
-- Indexes for table `budget_revisions`
--
ALTER TABLE `budget_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `cost_center_id` (`cost_center_id`);

--
-- Indexes for table `document_lines`
--
ALTER TABLE `document_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `portal_access`
--
ALTER TABLE `portal_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `contact_id` (`contact_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `cost_center_id` (`cost_center_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auto_analytical_rules`
--
ALTER TABLE `auto_analytical_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `budget_revisions`
--
ALTER TABLE `budget_revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cost_centers`
--
ALTER TABLE `cost_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `document_lines`
--
ALTER TABLE `document_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `portal_access`
--
ALTER TABLE `portal_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auto_analytical_rules`
--
ALTER TABLE `auto_analytical_rules`
  ADD CONSTRAINT `auto_analytical_rules_ibfk_1` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`);

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`);

--
-- Constraints for table `budget_revisions`
--
ALTER TABLE `budget_revisions`
  ADD CONSTRAINT `budget_revisions_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`);

--
-- Constraints for table `document_lines`
--
ALTER TABLE `document_lines`
  ADD CONSTRAINT `document_lines_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`),
  ADD CONSTRAINT `document_lines_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`);

--
-- Constraints for table `portal_access`
--
ALTER TABLE `portal_access`
  ADD CONSTRAINT `portal_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `portal_access_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
