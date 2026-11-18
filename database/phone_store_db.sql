-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 18, 2026 lúc 11:18 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `phone_store_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `full_address` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `status` enum('active','hidden') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `status`) VALUES
(1, 'Apple', 'apple', 'active'),
(2, 'Samsung', 'samsung', 'active'),
(3, 'Xiaomi', 'xiaomi', 'active'),
(4, 'OPPO', 'oppo', 'active'),
(5, 'vivo', 'vivo', 'active'),
(6, 'realme', 'realme', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `status` enum('active','hidden') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `status`) VALUES
(1, 'Điện thoại di động', 'dien-thoai', 'active'),
(2, 'Tai nghe & Âm thanh', 'phu-kien', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL,
  `discount_value` decimal(12,0) NOT NULL,
  `min_order_amount` decimal(12,0) DEFAULT 0,
  `usage_limit` int(11) DEFAULT 100,
  `used_count` int(11) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `status` enum('active','paused') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `usage_limit`, `used_count`, `expires_at`, `status`) VALUES
(1, 'KHUYENMAI10', 'percent', 10, 5000000, 50, 0, '2028-12-31 23:59:59', 'active'),
(2, 'GIAM500K', 'fixed', 500000, 10000000, 100, 0, '2028-12-31 23:59:59', 'active'),
(3, 'SALE500K', 'percent', 500000, 1, 100, 0, '2028-12-31 20:27:00', 'active'),
(5, 'SALE600K', 'fixed', 600000, 1, 100, 0, '2026-10-17 20:45:00', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupon_usages`
--

CREATE TABLE `coupon_usages` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `used_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `shipping_address` text NOT NULL,
  `note` text DEFAULT NULL,
  `subtotal` decimal(12,0) NOT NULL,
  `discount_amount` decimal(12,0) DEFAULT 0,
  `shipping_fee` decimal(12,0) DEFAULT 0,
  `total_amount` decimal(12,0) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','processing','shipping','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `customer_phone`, `shipping_address`, `note`, `subtotal`, `discount_amount`, `shipping_fee`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `created_at`) VALUES
(1, 'ORD20261016154816568', NULL, 'Hợp Phạm', '1234567890', 'áaaaaaa', '', 13490000, 0, 0, 13490000, 'BANK_TRANSFER', 'pending', 'confirmed', '2026-10-16 20:48:16'),
(2, 'ORD20261016155055803', 1, 'Hợp Phạm', '123456789', 'aaaaa', '', 13490000, 1349000, 0, 12141000, 'BANK_TRANSFER', 'pending', 'cancelled', '2026-10-16 20:50:55'),
(3, 'ORD20261016155744379', NULL, 'Hợp Phạm', '1234567890', '12', '', 19990000, 0, 0, 19990000, 'COD', 'paid', 'completed', '2026-10-16 20:57:44'),
(4, 'ORD20261016160722370', 2, 'Hợp Phạm', '123', 'aaaaa', '', 26980000, 0, 0, 26980000, 'BANK_TRANSFER', 'pending', 'cancelled', '2026-10-16 21:07:22'),
(5, 'ORD20261016161530625', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'BANK_TRANSFER', 'pending', 'cancelled', '2026-10-16 21:15:30'),
(6, 'ORD20261016161638646', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'BANK_TRANSFER', 'pending', 'cancelled', '2026-10-16 21:16:38'),
(7, 'ORD179216296346', 2, 'Hợp Phạm', '123', 'aaaaa', '', 29590000, 0, 0, 29590000, 'PAYOS', 'pending', 'cancelled', '2026-10-16 22:02:43'),
(8, 'ORD179216298995', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'MOMO', 'pending', 'cancelled', '2026-10-16 22:03:09'),
(9, 'ORD179223854421', 2, 'Hợp Phạm', '123', 'aaaaa', '', 8490000, 0, 0, 8490000, 'MOMO', 'pending', 'cancelled', '2026-10-17 19:02:24'),
(10, 'ORD179223919294', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'MOMO', 'pending', 'cancelled', '2026-10-17 19:13:12'),
(12, 'ORD179223941654', 2, 'Hợp Phạm', '123', 'aaaaa', '', 19990000, 0, 0, 19990000, 'COD', 'pending', 'cancelled', '2026-10-17 19:16:56'),
(13, 'ORD179223943275', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'PAYOS', 'pending', 'cancelled', '2026-10-17 19:17:12'),
(14, 'ORD179223945297', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'VNPAY', 'pending', 'cancelled', '2026-10-17 19:17:32'),
(16, 'ORD179223969899', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'COD', 'pending', 'cancelled', '2026-10-17 19:21:38'),
(17, 'ORD179223971414', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'PAYOS', 'paid', 'cancelled', '2026-10-17 19:21:54'),
(18, 'ORD2026101714301537', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'VISA', 'paid', 'processing', '2026-10-17 19:30:15'),
(19, 'ORD2026101714303567', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'BANK_QR', 'paid', 'confirmed', '2026-10-17 19:30:35'),
(20, 'ORD2026101714513352', 2, 'Hợp Phạm', '123', 'aaaaa', '', 13490000, 0, 0, 13490000, 'BANK_QR', 'paid', 'confirmed', '2026-10-17 19:51:33'),
(21, 'ORD2026101715063412', 2, 'Hợp Phạm', '123', 'aaaaa', '', 19990000, 0, 0, 19990000, 'BANK_QR', 'paid', 'completed', '2026-10-17 20:06:34'),
(22, 'ORD2026101715104547', 2, 'Hợp Phạm', '123', 'aaaaa', '', 19990000, 0, 0, 19990000, 'BANK_QR', 'paid', 'completed', '2026-10-17 20:10:45'),
(23, 'ORD2026101715462183', 2, 'Hợp Phạm', '123', 'aaaaa', '', 24990000, 500000, 0, 24490000, 'BANK_QR', 'paid', 'completed', '2026-10-17 20:46:21'),
(24, 'ORD2026101715481179', 2, 'Hợp Phạm', '123', 'aaaaa', '', 14490000, 500000, 0, 13990000, 'BANK_QR', 'paid', 'completed', '2026-10-17 20:48:11'),
(25, 'ORD2026101716361985', 2, 'Hợp Phạm', '123', 'aaaaa', '', 4890000, 0, 0, 4890000, 'BANK_QR', 'paid', 'completed', '2026-10-17 21:36:19'),
(26, 'ORD2026101717434073', 2, 'Hợp Phạm', '123', 'aaaaa', '', 39990000, 0, 0, 39990000, 'BANK_QR', 'paid', 'completed', '2026-10-17 22:43:40'),
(27, 'ORD2026101718361465', 2, 'Hợp Phạm', '123', 'aaaaa', '', 39990000, 600000, 0, 39390000, 'BANK_QR', 'paid', 'completed', '2026-10-17 23:36:14'),
(28, 'ORD2026101718402885', 2, 'Hợp Phạm', '123', 'aaaaa', '', 39990000, 0, 0, 39990000, 'BANK_QR', 'paid', 'completed', '2026-10-17 23:40:28'),
(29, 'ORD2026101718423915', 2, 'Hợp Phạm', '123', 'aaaaa', '', 39990000, 0, 0, 39990000, 'BANK_QR', 'paid', 'cancelled', '2026-10-17 23:42:39'),
(30, 'ORD2026101817220040', 4, 'cccc', '1234', 'aaaaa', '', 79980000, 7998000, 0, 71982000, 'BANK_QR', 'paid', 'completed', '2026-10-18 22:22:00'),
(31, 'ORD2026101818264491', 4, 'cccc', '1234', 'aaaaa', '', 39990000, 3999000, 0, 35991000, 'BANK_QR', 'paid', 'confirmed', '2026-10-18 23:26:44'),
(32, 'ORD2026101818321178', 4, 'cccc', '1234', 'aaaaa', '', 39990000, 0, 0, 39990000, 'BANK_QR', 'paid', 'confirmed', '2026-10-18 23:32:11'),
(33, 'ORD2026101818330729', 4, 'cccc', '1234', 'aaaaa', '', 39990000, 0, 0, 39990000, 'BANK_QR', 'paid', 'confirmed', '2026-10-18 23:33:07'),
(34, 'ORD2026102114053427', 4, 'cccc', '1234', 'aaaaa', '', 39990000, 3999000, 0, 35991000, 'BANK_QR', 'paid', 'completed', '2026-10-21 19:05:34'),
(35, 'ORD2026102208301934', 5, 'ẻtgyhj', '1234567', 'dfghjkm', '', 26980000, 2698000, 0, 24282000, 'BANK_QR', 'paid', 'confirmed', '2026-10-22 13:30:19'),
(36, 'ORD2026102209375845', 4, 'cccc', '1234', 'aaaaa', '', 39990000, 0, 0, 39990000, 'BANK_QR', 'pending', 'pending', '2026-10-22 14:37:58'),
(37, 'ORD2026102209401135', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'BANK_QR', 'pending', 'pending', '2026-10-22 14:40:11'),
(38, 'ORD2026102209423991', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'BANK_QR', 'pending', 'pending', '2026-10-22 14:42:39'),
(39, 'ORD2026102210273137', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'BANK_QR', 'pending', 'pending', '2026-10-22 15:27:31'),
(40, 'ORD2026102210462570', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'BANK_QR', 'paid', 'confirmed', '2026-10-22 15:46:25'),
(41, 'ORD2026102210522097', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'BANK_QR', 'paid', 'confirmed', '2026-10-22 15:52:20'),
(42, 'ORD2026110517275837', 4, 'cccc', '1234', 'aaaaa', '', 39990000, 0, 0, 39990000, 'BANK_QR', 'pending', 'pending', '2026-11-05 23:27:58'),
(43, 'ORD2026110517282558', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'COD', 'pending', 'cancelled', '2026-11-05 23:28:25'),
(44, 'ORD2026110517283971', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'BANK_QR', 'pending', 'pending', '2026-11-05 23:28:39'),
(45, 'ORD2026110517390651', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'BANK_QR', 'paid', 'confirmed', '2026-11-05 23:39:06'),
(46, 'ORD2026111215300648', 4, 'cccc', '1234', 'aaaaa', '', 2000, 0, 0, 2000, 'BANK_QR', 'pending', 'pending', '2026-11-12 21:30:06');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(12,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total_price`) VALUES
(1, 1, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(2, 2, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(3, 3, 3, 'Xiaomi 14 5G 12GB/256GB', 19990000, 1, 19990000),
(4, 4, 5, 'iPhone 13 128GB', 13490000, 2, 26980000),
(5, 5, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(6, 6, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(7, 7, 1, 'iPhone 15 Pro Max 256GB', 29590000, 1, 29590000),
(8, 8, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(9, 9, 4, 'OPPO Reno11 F 5G 8GB/256GB', 8490000, 1, 8490000),
(10, 10, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(12, 12, 3, 'Xiaomi 14 5G 12GB/256GB', 19990000, 1, 19990000),
(13, 13, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(14, 14, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(16, 16, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(17, 17, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(18, 18, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(19, 19, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(20, 20, 5, 'iPhone 13 128GB', 13490000, 1, 13490000),
(21, 21, 3, 'Xiaomi 14 5G 12GB/256GB', 19990000, 1, 19990000),
(22, 22, 3, 'Xiaomi 14 5G 12GB/256GB', 19990000, 1, 19990000),
(23, 23, 23, 'vivo X100 Pro 5G 16GB/512GB', 24990000, 1, 24990000),
(24, 24, 26, 'realme GT 6 5G 12GB/256GB', 14490000, 1, 14490000),
(25, 25, 30, 'Tai nghe Samsung Galaxy Buds3 Pro', 4890000, 1, 4890000),
(26, 26, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(27, 27, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(28, 28, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(29, 29, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(30, 30, 31, 'iPhone 16 Pro Max 1TB', 39990000, 2, 79980000),
(31, 31, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(32, 32, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(33, 33, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(34, 34, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(35, 35, 9, 'iPhone 15 128GB', 18490000, 1, 18490000),
(36, 35, 4, 'OPPO Reno11 F 5G 8GB/256GB', 8490000, 1, 8490000),
(37, 36, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(38, 37, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000),
(39, 38, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000),
(40, 39, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000),
(41, 40, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000),
(42, 41, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000),
(43, 42, 31, 'iPhone 16 Pro Max 1TB', 39990000, 1, 39990000),
(44, 43, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000),
(45, 44, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000),
(46, 45, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000),
(47, 46, 83, 'Xác nhận thanh toán PhoneStore', 2000, 1, 2000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `old_status`, `new_status`, `changed_by`, `note`, `created_at`) VALUES
(1, 22, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:34:22'),
(2, 22, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:34:25'),
(3, 22, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:34:27'),
(4, 3, 'pending', 'confirmed', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:37:05'),
(5, 3, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:37:10'),
(6, 3, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:37:12'),
(7, 3, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:37:13'),
(8, 23, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:46:50'),
(9, 23, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:47:04'),
(10, 23, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:47:12'),
(11, 24, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:48:30'),
(12, 24, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:48:37'),
(13, 24, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-17 20:48:44'),
(14, 21, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-17 21:10:19'),
(15, 25, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-17 21:36:57'),
(16, 25, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-17 21:37:00'),
(17, 25, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-17 21:37:06'),
(18, 26, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-17 22:43:59'),
(19, 26, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-17 22:44:01'),
(20, 26, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-17 22:44:03'),
(21, 27, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-17 23:37:09'),
(22, 27, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-17 23:37:16'),
(23, 27, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-17 23:37:26'),
(24, 30, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-18 22:22:32'),
(25, 30, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-18 22:22:34'),
(26, 30, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-18 22:22:37'),
(27, 29, 'confirmed', 'cancelled', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:12:24'),
(28, 28, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:12:39'),
(29, 28, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:12:46'),
(30, 28, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:12:52'),
(31, 21, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:23:03'),
(32, 21, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:23:11'),
(33, 18, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:24:07'),
(34, 17, 'confirmed', 'cancelled', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:24:29'),
(35, 1, 'pending', 'confirmed', 3, 'Admin cập nhật trạng thái', '2026-10-18 23:33:39'),
(36, 34, 'confirmed', 'processing', 3, 'Admin cập nhật trạng thái', '2026-10-21 19:07:36'),
(37, 34, 'processing', 'shipping', 3, 'Admin cập nhật trạng thái', '2026-10-21 19:07:40'),
(38, 34, 'shipping', 'completed', 3, 'Admin cập nhật trạng thái', '2026-10-21 19:07:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `price_original` decimal(12,0) NOT NULL,
  `price_sale` decimal(12,0) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` text NOT NULL,
  `description` text DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','hidden','discontinued') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `sku`, `name`, `slug`, `price_original`, `price_sale`, `stock`, `image`, `description`, `specs`, `is_featured`, `created_at`, `status`) VALUES
(1, 1, 1, 'IP15PM-256', 'iPhone 15 Pro Max 256GB', 'iphone-15-pro-max-256gb', 34990000, 29590000, 15, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-15-pro-max.jpg', 'iPhone 15 Pro Max với thiết kế khung Titanium cao cấp, chip A17 Pro mạnh mẽ vượt trội và hệ thống camera zoom quang học 5x sắc nét.', '{\"Màn hình\": \"6.7 inch OLED Super Retina XDR\", \"Chip\": \"Apple A17 Pro 6 nhân\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"256 GB\", \"Camera sau\": \"Chính 48 MP & Phụ 12 MP, 12 MP\", \"Pin\": \"4422 mAh\"}', 1, '2026-10-16 20:38:07', 'active'),
(2, 1, 2, 'SS-S24U-256', 'Samsung Galaxy S24 Ultra 5G 256GB', 'samsung-galaxy-s24-ultra-256gb', 33990000, 27990000, 10, 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-s24-ultra-5g-sm-s928-stylus.jpg', 'Samsung Galaxy S24 Ultra trang bị quyền năng Galaxy AI tiên tiến, bút S Pen tích hợp, camera 200MP xuất sắc và khung viền Titanium bền bỉ.', '{\"Màn hình\": \"6.8 inch Dynamic AMOLED 2X\", \"Chip\": \"Snapdragon 8 Gen 3 for Galaxy\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Camera sau\": \"200 MP & 50 MP, 12 MP, 10 MP\", \"Pin\": \"5000 mAh\"}', 1, '2026-10-16 20:38:07', 'active'),
(3, 1, 3, 'XM-14-256', 'Xiaomi 14 5G 12GB/256GB', 'xiaomi-14-5g-256gb', 22990000, 19990000, 5, 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-14-new.jpg', 'Xiaomi 14 ống kính Leica cao cấp, hiệu năng đỉnh cao với chip Snapdragon 8 Gen 3 và sạc siêu nhanh 90W.', '{\"Màn hình\": \"6.36 inch AMOLED 120Hz\", \"Chip\": \"Snapdragon 8 Gen 3\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Camera sau\": \"3 ống kính Leica 50 MP\", \"Pin\": \"4610 mAh\"}', 1, '2026-10-16 20:38:07', 'active'),
(4, 1, 4, 'OPPO-R11F', 'OPPO Reno11 F 5G 8GB/256GB', 'oppo-reno11-f-5g', 8990000, 8490000, 19, 'https://fdn2.gsmarena.com/vv/bigpic/oppo-reno11-f-5g.jpg', 'OPPO Reno11 F sở hữu thiết kế vân kim cương độc đáo, camera chân thực nét chuẩn chuyên gia và sạc nhanh SUPERVOOC 67W.', '{\"Màn hình\": \"6.7 inch AMOLED 120Hz\", \"Chip\": \"MediaTek Dimensity 7050 5G\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"256 GB\", \"Camera sau\": \"Chính 64 MP & Phụ 8 MP, 2 MP\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-16 20:38:07', 'active'),
(5, 1, 1, 'IP13-128', 'iPhone 13 128GB', 'iphone-13-128gb', 17990000, 13490000, 1, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-13.jpg', 'iPhone 13 đem lại thời lượng pin ấn tượng, cụm camera chéo độc đáo với chế độ Điện ảnh Cinematic sắc nét.', '{\"Màn hình\": \"6.1 inch Super Retina XDR\", \"Chip\": \"Apple A15 Bionic\", \"RAM\": \"4 GB\", \"Bộ nhớ\": \"128 GB\", \"Camera sau\": \"2 camera 12 MP\", \"Pin\": \"3240 mAh\"}', 1, '2026-10-16 20:38:07', 'active'),
(6, 1, 1, 'IP16PM-256', 'iPhone 16 Pro Max 256GB', 'iphone-16-pro-max-256gb', 34990000, 33490000, 20, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-16-pro-max.jpg', 'iPhone 16 Pro Max chip A18 Pro, nút Camera Control mới.', '{\"Màn hình\": \"6.9 inch OLED\", \"Chip\": \"Apple A18 Pro\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Titan Sa Mạc\", \"Pin\": \"4685 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(7, 1, 1, 'IP16P-128', 'iPhone 16 Pro 128GB', 'iphone-16-pro-128gb', 28990000, 27990000, 12, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-16-pro.jpg', 'Thiết kế Titan nhỏ gọn, màn hình ProMotion 120Hz.', '{\"Màn hình\": \"6.3 inch OLED\", \"Chip\": \"Apple A18 Pro\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"128 GB\", \"Màu sắc\": \"Titan Tự Nhiên\", \"Pin\": \"3582 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(8, 1, 1, 'IP16-128', 'iPhone 16 128GB', 'iphone-16-128gb', 22990000, 21490000, 18, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-16.jpg', 'iPhone 16 thế hệ mới, hiệu năng vượt trội.', '{\"Màn hình\": \"6.1 inch OLED\", \"Chip\": \"Apple A18\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"128 GB\", \"Màu sắc\": \"Xanh Lưu Ly\", \"Pin\": \"3561 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(9, 1, 1, 'IP15-128', 'iPhone 15 128GB', 'iphone-15-128gb', 19990000, 18490000, 8, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-15.jpg', 'Dynamic Island tiện lợi, cổng sạc USB-C.', '{\"Màn hình\": \"6.1 inch OLED\", \"Chip\": \"Apple A16 Bionic\", \"RAM\": \"6 GB\", \"Bộ nhớ\": \"128 GB\", \"Màu sắc\": \"Hồng\", \"Pin\": \"3349 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(10, 1, 1, 'IP14PM-128', 'iPhone 14 Pro Max 128GB', 'iphone-14-pro-max-128gb', 27990000, 24990000, 10, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-14-pro-max-.jpg', 'Màn hình Always-On, camera 48MP sắc nét (Sản phẩm hết hàng để test).', '{\"Màn hình\":\"6.7 inch OLED\",\"Chip\":\"Apple A16 Bionic\",\"RAM\":\"6 GB\",\"Bộ nhớ\":\"128 GB\",\"Màu sắc\":\"Tím Deep Purple\",\"Pin\":\"4323 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(11, 1, 2, 'SS-S24P-256', 'Samsung Galaxy S24 Plus 5G 256GB', 'samsung-galaxy-s24-plus-256gb', 26990000, 23990000, 14, 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-s24-plus-5g-sm-s926.jpg', 'Màn hình QHD+ sắc nét, tích hợp quyền năng Galaxy AI.', '{\"Màn hình\": \"6.7 inch Dynamic AMOLED 2X\", \"Chip\": \"Exynos 2400\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Xám Marble\", \"Pin\": \"4900 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(12, 1, 2, 'SS-ZFOLD6-256', 'Samsung Galaxy Z Fold6 5G 256GB', 'samsung-galaxy-z-fold6-256gb', 43990000, 40990000, 3, 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-z-fold6.jpg', 'Điện thoại gập đỉnh cao đa nhiệm.', '{\"Màn hình\": \"7.6 inch Foldable\", \"Chip\": \"Snapdragon 8 Gen 3\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Bạc Shadow\", \"Pin\": \"4400 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(13, 1, 2, 'SS-ZFLIP6-256', 'Samsung Galaxy Z Flip6 5G 256GB', 'samsung-galaxy-z-flip6-256gb', 28990000, 25990000, 7, 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-z-flip6.jpg', 'Gập nhỏ gọn thời trang, FlexCam thông minh.', '{\"Màn hình\": \"6.7 inch Foldable\", \"Chip\": \"Snapdragon 8 Gen 3\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Xanh Mint\", \"Pin\": \"4000 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(14, 1, 2, 'SS-A55-128', 'Samsung Galaxy A55 5G 8GB/128GB', 'samsung-galaxy-a55-5g', 9990000, 9190000, 25, 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a55.jpg', 'Khung viền kim loại, kháng nước IP67.', '{\"Màn hình\": \"6.6 inch Super AMOLED\", \"Chip\": \"Exynos 1480\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"128 GB\", \"Màu sắc\": \"Tím Lilac\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(15, 1, 2, 'SS-A35-128', 'Samsung Galaxy A35 5G 8GB/128GB', 'samsung-galaxy-a35-5g', 8290000, 7590000, 19, 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a35.jpg', 'Màn hình 120Hz mượt mà, camera chống rung OIS.', '{\"Màn hình\": \"6.6 inch Super AMOLED\", \"Chip\": \"Exynos 1380\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"128 GB\", \"Màu sắc\": \"Xanh Iceblue\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(16, 1, 3, 'XM-14U-512', 'Xiaomi 14 Ultra 5G 16GB/512GB', 'xiaomi-14-ultra-512gb', 32990000, 29990000, 4, 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-14-ultra-new.jpg', 'Hệ thống 4 camera Leica đỉnh cao nhiếp ảnh.', '{\"Màn hình\": \"6.73 inch AMOLED\", \"Chip\": \"Snapdragon 8 Gen 3\", \"RAM\": \"16 GB\", \"Bộ nhớ\": \"512 GB\", \"Màu sắc\": \"Đen\", \"Pin\": \"5000 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(17, 1, 3, 'XM-14TP-512', 'Xiaomi 14T Pro 5G 12GB/512GB', 'xiaomi-14t-pro-512gb', 16990000, 15490000, 11, 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-14t-pro.jpg', 'Sạc siêu nhanh 120W, màn hình 144Hz.', '{\"Màn hình\": \"6.67 inch AMOLED 144Hz\", \"Chip\": \"Dimensity 9300+\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"512 GB\", \"Màu sắc\": \"Xám Titan\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(18, 1, 3, 'XM-RN13P-256', 'Xiaomi Redmi Note 13 Pro 5G 256GB', 'redmi-note-13-pro-5g', 9490000, 8690000, 22, 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-redmi-note-13-pro-5g.jpg', 'Camera 200MP OIS, viền màn hình siêu mỏng.', '{\"Màn hình\": \"6.67 inch AMOLED 1.5K\", \"Chip\": \"Snapdragon 7s Gen 2\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Tím Cực Quang\", \"Pin\": \"5100 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(19, 1, 3, 'XM-POCOF6-256', 'Xiaomi POCO F6 5G 12GB/256GB', 'xiaomi-poco-f6-5g', 10990000, 9990000, 0, 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-poco-f6.jpg', 'Hiệu năng chiến game đỉnh cao (Sản phẩm hết hàng).', '{\"Màn hình\": \"6.67 inch AMOLED\", \"Chip\": \"Snapdragon 8s Gen 3\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Đen\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(20, 1, 4, 'OPPO-FX8-256', 'OPPO Find X8 5G 16GB/256GB', 'oppo-find-x8-5g', 22990000, 21490000, 8, 'https://fdn2.gsmarena.com/vv/bigpic/oppo-find-x8.jpg', 'Camera Hasselblad tiềm vọng, pin Silicon-Carbon.', '{\"Màn hình\": \"6.59 inch AMOLED\", \"Chip\": \"Dimensity 9400\", \"RAM\": \"16 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Bạc\", \"Pin\": \"5630 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(21, 1, 4, 'OPPO-R12-256', 'OPPO Reno12 5G 12GB/256GB', 'oppo-reno12-5g', 12990000, 11990000, 16, 'https://fdn2.gsmarena.com/vv/bigpic/oppo-reno12.jpg', 'Nhiếp ảnh AI xóa vật thể, thiết kế dòng chảy bạc.', '{\"Màn hình\": \"6.7 inch AMOLED\", \"Chip\": \"Dimensity 7300-Energy\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Bạc Vũ Trụ\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(22, 1, 4, 'OPPO-A60-128', 'OPPO A60 8GB/128GB', 'oppo-a60-128gb', 5490000, 4990000, 30, 'https://fdn2.gsmarena.com/vv/bigpic/oppo-a60.jpg', 'Siêu bền chuẩn quân đội, kháng nước cảm ứng.', '{\"Màn hình\": \"6.67 inch IPS LCD\", \"Chip\": \"Snapdragon 680\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"128 GB\", \"Màu sắc\": \"Xanh Đen\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(23, 1, 5, 'VIVO-X100P-512', 'vivo X100 Pro 5G 16GB/512GB', 'vivo-x100-pro-5g', 26990000, 24990000, 1, 'https://fdn2.gsmarena.com/vv/bigpic/vivo-x100-pro.jpg', 'Ống kính ZEISS APO siêu tele chuyên nghiệp.', '{\"Màn hình\": \"6.78 inch LTPO AMOLED\", \"Chip\": \"Dimensity 9300\", \"RAM\": \"16 GB\", \"Bộ nhớ\": \"512 GB\", \"Màu sắc\": \"Cam Hoàng Hôn\", \"Pin\": \"5400 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(24, 1, 5, 'VIVO-V30-256', 'vivo V30 5G 12GB/256GB', 'vivo-v30-5g', 13990000, 12490000, 13, 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=500&q=80', 'Vòng sáng Aura 3.0 chụp chân dung ban đêm.', '{\"Màn hình\": \"6.78 inch AMOLED\", \"Chip\": \"Snapdragon 7 Gen 3\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Trắng Hoa Tuyết\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(25, 1, 5, 'VIVO-Y100-128', 'vivo Y100 8GB/128GB', 'vivo-y100-128gb', 7290000, 6690000, 17, 'https://images.unsplash.com/photo-1580910051074-3eb694886505?auto=format&fit=crop&w=500&q=80', 'Sạc siêu tốc FlashCharge 80W, màn hình AMOLED.', '{\"Màn hình\": \"6.67 inch AMOLED 120Hz\", \"Chip\": \"Snapdragon 685\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"128 GB\", \"Màu sắc\": \"Xanh Lục Bảo\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(26, 1, 6, 'RM-GT6-256', 'realme GT 6 5G 12GB/256GB', 'realme-gt-6-5g', 15990000, 14490000, 5, 'https://fdn2.gsmarena.com/vv/bigpic/realme-gt6.jpg', 'Màn hình siêu sáng 6000 nits, hiệu năng flagship.', '{\"Màn hình\": \"6.78 inch LTPO AMOLED\", \"Chip\": \"Snapdragon 8s Gen 3\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Bạc Gương\", \"Pin\": \"5500 mAh\"}', 1, '2026-10-17 20:19:45', 'active'),
(27, 1, 6, 'RM-12P-256', 'realme 12 Pro+ 5G 12GB/256GB', 'realme-12-pro-plus-5g', 13990000, 12290000, 9, 'https://fdn2.gsmarena.com/vv/bigpic/realme-12-pro-plus.jpg', 'Mặt lưng da cao cấp, camera tiềm vọng 64MP.', '{\"Màn hình\": \"6.7 inch AMOLED\", \"Chip\": \"Snapdragon 7s Gen 2\", \"RAM\": \"12 GB\", \"Bộ nhớ\": \"256 GB\", \"Màu sắc\": \"Xanh Hải Quân\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(28, 1, 6, 'RM-C67-128', 'realme C67 8GB/128GB', 'realme-c67-128gb', 4690000, 4190000, 28, 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=500&q=80', 'Camera 108MP zoom 3x trong cảm biến.', '{\"Màn hình\": \"6.72 inch IPS LCD 90Hz\", \"Chip\": \"Snapdragon 685\", \"RAM\": \"8 GB\", \"Bộ nhớ\": \"128 GB\", \"Màu sắc\": \"Xanh ốc đảo\", \"Pin\": \"5000 mAh\"}', 0, '2026-10-17 20:19:45', 'active'),
(29, 2, 1, 'PK-AIRPODS4', 'Tai nghe Apple AirPods 4', 'apple-airpods-4', 3990000, 3490000, 24, 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?auto=format&fit=crop&w=500&q=80', 'Âm thanh không gian cá nhân hóa, chip H2.', '{\"Kết nối\": \"Bluetooth 5.3\", \"Chip\": \"Apple H2\", \"Cổng sạc\": \"USB-C\", \"Màu sắc\": \"Trắng\", \"Pin\": \"30 giờ kèm hộp sạc\"}', 0, '2026-10-17 20:19:45', 'active'),
(30, 2, 2, 'PK-SSBUDS3P', 'Tai nghe Samsung Galaxy Buds3 Pro', 'samsung-galaxy-buds3-pro', 5490000, 4890000, 9, 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=500&q=80', 'Chống ồn chủ động ANC thông minh với Galaxy AI.', '{\"Kết nối\": \"Bluetooth 5.4\", \"Chống nước\": \"IP57\", \"Cổng sạc\": \"USB-C\", \"Màu sắc\": \"Bạc\", \"Pin\": \"26 giờ kèm hộp sạc\"}', 0, '2026-10-17 20:19:45', 'active'),
(31, 1, 1, 'SP31', 'iPhone 16 Pro Max 1TB', 'iphone-16-pro-max-1tb', 44990000, 39990000, 14, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-16-pro-max.jpg', 'Chip Apple A18 Pro mạnh mẽ, màn hình Super Retina XDR 6.9 inch, camera 48MP đỉnh cao.', NULL, 0, '2026-10-17 21:40:40', 'active'),
(83, 1, 1, 'IP14PM-129', 'Xác nhận thanh toán PhoneStore', 'x-c-nh-n-thanh-to-n-phonestore-ip14pm-129', 2000, 2000, 2, '', '', '{\"Màn hình\":\"\",\"Chip\":\"\",\"RAM\":\"\",\"Bộ nhớ\":\"\",\"Màu sắc\":\"\",\"Pin\":\"\"}', 0, '2026-10-22 14:39:44', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `storage` varchar(50) NOT NULL,
  `price_sale` decimal(12,0) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `comment` text NOT NULL,
  `status` enum('visible','hidden') NOT NULL DEFAULT 'visible',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(18, 31, 2, 3, 'qqqq', 'visible', '2026-10-18 22:14:24'),
(19, 31, 4, 1, 'ttt', 'visible', '2026-10-18 22:19:41'),
(20, 31, 4, 5, 'yyy', 'visible', '2026-10-18 22:46:18'),
(21, 31, 4, 5, 'yyy', 'visible', '2026-10-18 23:25:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `carrier` varchar(100) DEFAULT 'GHN',
  `tracking_code` varchar(100) DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','locked') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `fullname`, `phone`, `email`, `password`, `address`, `role`, `created_at`, `status`) VALUES
(1, 'Hợp Phạm', '123456789', 'hop1052005@gmail.com', '$2y$10$isPoNpnSjivL9lWa/pav8eHeOVmzAeeITV3F2K6npJnC9Fibapxn.', 'aaaaa', 'customer', '2026-10-16 20:48:32', 'active'),
(2, 'Hợp Phạm', '123', 'hop10520051@gmail.com', '$2y$10$Guyn.LDJ77kXhnwhZ1OaPOCgIu3/2Y8y9sDS4EzlQH2t3xku.edjS', 'aaaaa', 'customer', '2026-10-16 21:07:08', 'active'),
(3, 'Quản Trị Viên', '0909999999', 'admin@gmail.com', '$2y$10$ycBAOgICeC7XrCzEul/GMOuS6DsoT81J4n8fRKJZJb3G.WJNZDiJe', 'Hệ thống Admin', 'admin', '2026-10-17 20:12:08', 'active'),
(4, 'cccc', '1234', 'hop1052005@gmail.comm', '$2y$10$OhZVLvN5J.f1hWB6Fqg5huiV2oUU2adJypN1P0a5iAhr96X20waDS', 'aaaaa', 'customer', '2026-10-18 22:19:25', 'active'),
(5, 'ẻtgyhj', '1234567', 'dfgh@gmai.com', '$2y$10$uxoz4WPMPWPVMVWQBSLAR.vcBvR1PJCXOGwio8fqi965ctLBLafY6', 'dfghjkm', 'customer', '2026-10-22 13:29:19', 'active');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_prod_cat` (`category_id`),
  ADD KEY `fk_prod_brand` (`brand_id`);

--
-- Chỉ mục cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_prod` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `coupon_usages`
--
ALTER TABLE `coupon_usages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT cho bảng `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT cho bảng `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD CONSTRAINT `coupon_usages_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coupon_usages_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Các ràng buộc cho bảng `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_prod_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Các ràng buộc cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
