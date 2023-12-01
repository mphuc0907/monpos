-- phpMyAdmin SQL Dump
-- version 4.3.11
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2020 at 04:28 AM
-- Server version: 5.6.24
-- PHP Version: 5.6.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `qlbh`
--

-- --------------------------------------------------------

--
-- Table structure for table `cms_customers`
--

CREATE TABLE IF NOT EXISTS `cms_customers` (
  `ID` int(10) unsigned NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_code` varchar(10) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `customer_addr` varchar(255) NOT NULL,
  `notes` text NOT NULL,
  `customer_birthday` date NOT NULL,
  `customer_gender` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=49 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_customers`
--

INSERT INTO `cms_customers` (`ID`, `customer_name`, `customer_code`, `customer_phone`, `customer_email`, `customer_addr`, `notes`, `customer_birthday`, `customer_gender`, `created`, `updated`, `user_init`, `user_upd`) VALUES
(38, 'Tran Van A', 'Kh012', '0347811175', 'email@gmail.com', 'My Dinh - Ha Noi', 'Kh View', '1989-01-02', 0, '2020-04-16 09:01:45', '0000-00-00 00:00:00', 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `cms_input`
--

CREATE TABLE IF NOT EXISTS `cms_input` (
  `ID` int(10) unsigned NOT NULL,
  `input_code` varchar(9) NOT NULL,
  `supplier_id` int(11) NOT NULL DEFAULT '0',
  `store_id` int(11) NOT NULL DEFAULT '0',
  `input_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` varchar(255) NOT NULL,
  `payment_method` tinyint(4) NOT NULL DEFAULT '0',
  `total_price` int(13) NOT NULL DEFAULT '0',
  `total_quantity` int(9) NOT NULL DEFAULT '0',
  `discount` int(11) NOT NULL DEFAULT '0',
  `total_money` int(13) NOT NULL DEFAULT '0',
  `payed` int(11) NOT NULL DEFAULT '0',
  `lack` int(13) NOT NULL DEFAULT '0',
  `detail_input` text NOT NULL,
  `input_status` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `cms_inventory`
--

CREATE TABLE IF NOT EXISTS `cms_inventory` (
  `store_id` int(5) NOT NULL,
  `product_id` int(10) NOT NULL DEFAULT '0',
  `quantity` int(11) NOT NULL DEFAULT '0',
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_inventory`
--

INSERT INTO `cms_inventory` (`store_id`, `product_id`, `quantity`, `user_init`, `user_upd`, `created`, `updated`) VALUES
(2, 173, 0, 2, 2, '2020-04-16 09:53:29', '2020-04-17 09:26:32'),
(2, 172, 0, 2, 2, '2020-04-16 09:48:21', '2020-04-17 09:26:34'),
(1, 172, -1, 2, 2, '2020-04-16 09:02:31', '2020-04-17 09:26:26'),
(1, 174, -1, 2, NULL, '2020-04-16 09:02:31', NULL),
(1, 175, 0, 2, 2, '2020-04-16 08:40:43', '2020-04-17 09:26:36'),
(9, 175, 4, 2, NULL, '2020-01-21 21:31:22', NULL),
(9, 174, 2, 2, NULL, '2020-01-21 21:29:59', NULL),
(9, 173, 1, 2, NULL, '2020-01-21 21:23:28', NULL),
(9, 172, 8, 2, 2, '2020-01-09 20:34:57', '2020-03-03 21:04:56');

-- --------------------------------------------------------

--
-- Table structure for table `cms_orders`
--

CREATE TABLE IF NOT EXISTS `cms_orders` (
  `ID` int(10) unsigned NOT NULL,
  `output_code` varchar(9) NOT NULL,
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `store_id` int(11) NOT NULL DEFAULT '0',
  `sell_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` varchar(255) NOT NULL,
  `payment_method` tinyint(4) NOT NULL DEFAULT '0',
  `total_price` int(13) NOT NULL DEFAULT '0',
  `total_origin_price` int(11) NOT NULL DEFAULT '0',
  `coupon` int(11) NOT NULL DEFAULT '0',
  `customer_pay` int(11) NOT NULL DEFAULT '0',
  `total_money` int(13) NOT NULL DEFAULT '0',
  `total_quantity` int(9) NOT NULL DEFAULT '0',
  `lack` int(13) NOT NULL DEFAULT '0',
  `detail_order` text NOT NULL,
  `order_status` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) NOT NULL DEFAULT '0',
  `sale_id` int(5) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=350 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_orders`
--

INSERT INTO `cms_orders` (`ID`, `output_code`, `customer_id`, `store_id`, `sell_date`, `notes`, `payment_method`, `total_price`, `total_origin_price`, `coupon`, `customer_pay`, `total_money`, `total_quantity`, `lack`, `detail_order`, `order_status`, `deleted`, `created`, `updated`, `user_init`, `user_upd`, `sale_id`) VALUES
(349, 'PX0000008', 37, 1, '2020-04-17 09:55:00', '', 1, 6000000, 150000, 0, 6000000, 6000000, 1, 0, '[{"id":"172","quantity":"1","price":"6000000","discount":"0"}]', 1, 1, '2020-04-16 09:56:05', '2020-04-17 09:26:26', 13, 2, 13),
(348, 'PX0000007', 37, 2, '2020-04-16 09:53:43', '', 1, 6000000, 150000, 0, 6000000, 6000000, 1, 0, '[{"id":"172","quantity":"1","price":"6000000","discount":"0"}]', 1, 1, '2020-04-16 09:53:43', '2020-04-17 09:26:29', 2, 2, 12),
(347, 'PX0000006', 38, 2, '2020-04-16 09:53:29', '', 1, 1, 1, 0, 1, 1, 1, 0, '[{"id":"173","quantity":"1","price":"1","discount":"0"}]', 1, 1, '2020-04-16 09:53:29', '2020-04-17 09:26:32', 2, 2, 12),
(346, 'PX0000005', 37, 2, '2020-04-16 09:48:00', '', 1, 6000000, 150000, 0, 6000000, 6000000, 1, 0, '[{"id":"172","quantity":"1","price":"6000000","discount":"0"}]', 1, 1, '2020-04-16 09:48:21', '2020-04-17 09:26:34', 2, 2, 5),
(345, 'PX0000004', 38, 1, '2020-04-16 09:00:00', 'Mua 2 Sp', 1, 6000555, 150004, 100000, 900000, 5900555, 2, 5000555, '[{"id":"174","quantity":"1","price":"555","discount":"0"},{"id":"172","quantity":"1","price":"6000000","discount":"0"}]', 1, 0, '2020-04-16 09:02:31', '0000-00-00 00:00:00', 2, 0, 5),
(344, 'PX0000003', 37, 1, '2020-04-16 12:00:00', '', 1, 6000000, 3000000, 0, 6000000, 6000000, 1, 0, '[{"id":"175","quantity":"1","price":"6000000","discount":"0"}]', 1, 1, '2020-04-16 08:40:43', '2020-04-17 09:26:36', 2, 2, 9),
(343, 'PX0000002', 37, 9, '2020-03-03 21:04:56', '', 1, 6000000, 150000, 0, 6000000, 6000000, 1, 0, '[{"id":"172","quantity":"1","price":"6000000","discount":"0"}]', 1, 0, '2020-03-03 21:04:57', '0000-00-00 00:00:00', 2, 0, 0),
(342, 'PX0000001', 37, 9, '2020-01-18 20:51:05', '', 1, 6000000, 150000, 0, 6000000, 6000000, 1, 0, '[{"id":"172","quantity":"1","price":"6000000","discount":"0"}]', 1, 0, '2020-01-18 20:51:05', '0000-00-00 00:00:00', 2, 0, 9);

-- --------------------------------------------------------

--
-- Table structure for table `cms_permissions`
--

CREATE TABLE IF NOT EXISTS `cms_permissions` (
  `id` int(10) unsigned NOT NULL,
  `permission_url` varchar(255) NOT NULL,
  `permission_name` varchar(150) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_permissions`
--

INSERT INTO `cms_permissions` (`id`, `permission_url`, `permission_name`) VALUES
(1, 'backend/dashboard', 'Báo cáo mỗi ngày'),
(2, 'backend/order', 'Đơn hàng'),
(3, 'backend/product', 'Hàng Hóa'),
(5, 'backend/import', 'Nhập hàng'),
(6, 'backend/inventory', 'Báo cáo tồn kho'),
(10, 'backend/config', 'Thiết lập (Thông tin cửa hàng, nhân viên, thiết lập bán hàng, phân quyền)');

-- --------------------------------------------------------

--
-- Table structure for table `cms_picture`
--

CREATE TABLE IF NOT EXISTS `cms_picture` (
  `id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cms_products`
--

CREATE TABLE IF NOT EXISTS `cms_products` (
  `ID` int(10) unsigned NOT NULL,
  `prd_code` varchar(15) NOT NULL,
  `prd_name` varchar(255) NOT NULL,
  `prd_sls` int(11) NOT NULL DEFAULT '0',
  `prd_origin_price` int(11) NOT NULL DEFAULT '0',
  `prd_sell_price` int(11) NOT NULL DEFAULT '0',
  `prd_vat` tinyint(4) NOT NULL DEFAULT '0',
  `prd_status` tinyint(1) NOT NULL DEFAULT '1',
  `prd_inventory` tinyint(1) NOT NULL DEFAULT '0',
  `prd_allownegative` tinyint(1) NOT NULL DEFAULT '0',
  `prd_manufacture_id` int(11) NOT NULL DEFAULT '0',
  `prd_group_id` int(11) NOT NULL DEFAULT '0',
  `prd_image_url` varchar(255) NOT NULL,
  `prd_descriptions` text NOT NULL,
  `prd_manuf_id` int(11) NOT NULL DEFAULT '0',
  `prd_hot` tinyint(1) NOT NULL DEFAULT '0',
  `prd_new` tinyint(1) NOT NULL DEFAULT '0',
  `prd_highlight` tinyint(1) NOT NULL DEFAULT '0',
  `display_website` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=176 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_products`
--

INSERT INTO `cms_products` (`ID`, `prd_code`, `prd_name`, `prd_sls`, `prd_origin_price`, `prd_sell_price`, `prd_vat`, `prd_status`, `prd_inventory`, `prd_allownegative`, `prd_manufacture_id`, `prd_group_id`, `prd_image_url`, `prd_descriptions`, `prd_manuf_id`, `prd_hot`, `prd_new`, `prd_highlight`, `display_website`, `created`, `updated`, `user_init`, `user_upd`, `deleted`) VALUES
(175, 'SP00002', 'gggggg', 4, 3000000, 6000000, 0, 1, 0, 0, 8, 30, '', '', 0, 0, 0, 0, 0, '2020-01-21 21:31:22', '2020-04-17 09:27:47', 2, 0, 1),
(173, 'test2212', 'test2212', 1, 1, 1, 0, 1, 1, 0, 8, 30, '', '', 0, 0, 0, 0, 0, '2020-01-21 21:23:27', '2020-04-17 09:27:43', 2, 0, 1),
(174, 'SP00001', 'oppo k44', 1, 4, 555, 0, 1, 0, 0, 12, 30, '/qlbh/public/templates/backend/upload/admin/image/anh-xih1.jpg', '', 0, 0, 0, 0, 0, '2020-01-21 21:29:59', '2020-04-16 09:02:31', 2, 0, 0),
(172, 'testanh', 'test anh', 7, 150000, 6000000, 0, 1, 1, 0, 8, 0, '/qlbh/public/templates/backend/upload/admin/image/150606baoxaydung_image013.jpg', '<p>mở rộng ghi ch&uacute;</p>', 0, 0, 0, 0, 0, '2020-01-09 20:34:57', '2020-04-17 09:27:45', 2, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `cms_products_group`
--

CREATE TABLE IF NOT EXISTS `cms_products_group` (
  `ID` int(10) unsigned NOT NULL,
  `prd_group_name` varchar(255) NOT NULL,
  `parentid` int(11) NOT NULL DEFAULT '0',
  `level` tinyint(4) NOT NULL DEFAULT '0',
  `lft` int(11) NOT NULL DEFAULT '0',
  `rgt` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` tinyint(4) NOT NULL DEFAULT '0',
  `user_upd` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_products_group`
--

INSERT INTO `cms_products_group` (`ID`, `prd_group_name`, `parentid`, `level`, `lft`, `rgt`, `created`, `updated`, `user_init`, `user_upd`) VALUES
(33, 'test quan ao', 32, 1, 0, 0, '2020-04-16 16:46:47', '0000-00-00 00:00:00', 2, 0),
(32, 'Quan ao', -1, 0, 0, 0, '2020-04-16 08:57:11', '0000-00-00 00:00:00', 2, 0),
(30, 'Dien thoai', -1, 0, 0, 0, '2020-01-12 19:50:49', '0000-00-00 00:00:00', 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `cms_products_manufacture`
--

CREATE TABLE IF NOT EXISTS `cms_products_manufacture` (
  `ID` int(10) unsigned NOT NULL,
  `prd_manuf_name` varchar(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

--
-- Dumping data for table `cms_products_manufacture`
--

INSERT INTO `cms_products_manufacture` (`ID`, `prd_manuf_name`, `created`, `updated`, `user_init`, `user_upd`) VALUES
(8, 'Áo phông', '2017-10-20 14:18:46', '0000-00-00 00:00:00', 2, 0),
(9, 'Áo sơ mi', '2017-10-25 17:17:58', '0000-00-00 00:00:00', 2, 0),
(10, 'Đồ mặc nhà', '2017-10-29 18:14:30', '0000-00-00 00:00:00', 2, 0),
(11, 'Đồ công sở', '2017-10-31 17:31:50', '0000-00-00 00:00:00', 2, 0),
(12, 'oppo', '2019-10-12 14:36:16', '0000-00-00 00:00:00', 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `cms_report`
--

CREATE TABLE IF NOT EXISTS `cms_report` (
  `ID` int(10) unsigned NOT NULL,
  `transaction_code` varchar(9) NOT NULL,
  `transaction_id` int(10) NOT NULL DEFAULT '0',
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `store_id` int(5) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` varchar(255) NOT NULL,
  `product_id` int(10) NOT NULL DEFAULT '0',
  `discount` int(11) NOT NULL DEFAULT '0',
  `total_money` int(13) NOT NULL DEFAULT '0',
  `origin_price` int(11) NOT NULL DEFAULT '0',
  `input` int(11) NOT NULL DEFAULT '0',
  `output` int(9) NOT NULL DEFAULT '0',
  `price` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) NOT NULL DEFAULT '0',
  `sale_id` int(5) NOT NULL DEFAULT '0',
  `supplier_id` int(11) NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `stock` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=613 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_report`
--

INSERT INTO `cms_report` (`ID`, `transaction_code`, `transaction_id`, `customer_id`, `store_id`, `date`, `notes`, `product_id`, `discount`, `total_money`, `origin_price`, `input`, `output`, `price`, `deleted`, `created`, `updated`, `user_init`, `user_upd`, `sale_id`, `supplier_id`, `type`, `stock`) VALUES
(612, 'PX0000008', 349, 37, 1, '2020-04-17 09:55:00', '', 172, 0, 6000000, 150000, 0, 1, 6000000, 1, '2020-04-16 09:56:05', '2020-04-17 09:26:26', 13, 2, 13, 0, 3, -2),
(611, 'PX0000007', 348, 37, 2, '2020-04-16 09:53:43', '', 172, 0, 6000000, 150000, 0, 1, 6000000, 1, '2020-04-16 09:53:43', '2020-04-17 09:26:29', 2, 2, 12, 0, 3, -2),
(610, 'PX0000006', 347, 38, 2, '2020-04-16 09:53:29', '', 173, 0, 1, 1, 0, 1, 1, 1, '2020-04-16 09:53:29', '2020-04-17 09:26:32', 2, 2, 12, 0, 3, -1),
(609, 'PX0000005', 346, 37, 2, '2020-04-16 09:48:00', '', 172, 0, 6000000, 150000, 0, 1, 6000000, 1, '2020-04-16 09:48:21', '2020-04-17 09:26:34', 2, 2, 5, 0, 3, -1),
(608, 'PX0000004', 345, 38, 1, '2020-04-16 09:00:00', 'Mua 2 Sp', 172, 99991, 5900009, 150000, 0, 1, 6000000, 0, '2020-04-16 09:02:31', '0000-00-00 00:00:00', 2, 0, 5, 0, 3, -1),
(607, 'PX0000004', 345, 38, 1, '2020-04-16 09:00:00', 'Mua 2 Sp', 174, 9, 546, 4, 0, 1, 555, 0, '2020-04-16 09:02:31', '0000-00-00 00:00:00', 2, 0, 5, 0, 3, -1),
(606, 'PX0000003', 344, 37, 1, '2020-04-16 12:00:00', '', 175, 0, 6000000, 3000000, 0, 1, 6000000, 1, '2020-04-16 08:40:43', '2020-04-17 09:26:36', 2, 2, 9, 0, 3, -1),
(605, 'PX0000002', 343, 37, 9, '2020-03-03 21:04:56', '', 172, 0, 6000000, 150000, 0, 1, 6000000, 0, '2020-03-03 21:04:57', '0000-00-00 00:00:00', 2, 0, 0, 0, 3, 8),
(604, 'SP00002', 0, 0, 9, '2020-01-21 21:31:22', 'Khai báo hàng hóa', 175, 0, 0, 0, 4, 0, 0, 0, '2020-01-21 21:31:22', '0000-00-00 00:00:00', 2, 0, 0, 0, 1, 4),
(603, 'SP00001', 0, 0, 9, '2020-01-21 21:29:59', 'Khai báo hàng hóa', 174, 0, 0, 0, 2, 0, 0, 0, '2020-01-21 21:29:59', '0000-00-00 00:00:00', 2, 0, 0, 0, 1, 2),
(602, 'test2212', 0, 0, 9, '2020-01-21 21:23:28', 'Khai báo hàng hóa', 173, 0, 0, 0, 1, 0, 0, 0, '2020-01-21 21:23:28', '0000-00-00 00:00:00', 2, 0, 0, 0, 1, 1),
(601, 'PX0000001', 342, 37, 9, '2020-01-18 20:51:05', '', 172, 0, 6000000, 150000, 0, 1, 6000000, 0, '2020-01-18 20:51:05', '0000-00-00 00:00:00', 2, 0, 9, 0, 3, 9),
(600, 'testanh', 0, 0, 9, '2020-01-09 20:34:57', 'Khai báo hàng hóa', 172, 0, 0, 0, 10, 0, 0, 0, '2020-01-09 20:34:57', '0000-00-00 00:00:00', 2, 0, 0, 0, 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `cms_stores`
--

CREATE TABLE IF NOT EXISTS `cms_stores` (
  `ID` int(5) unsigned NOT NULL,
  `stock_name` varchar(255) NOT NULL,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_stores`
--

INSERT INTO `cms_stores` (`ID`, `stock_name`, `user_init`, `user_upd`, `created`, `updated`) VALUES
(1, 'Kho số 1', 1, 1, '2016-05-11 00:00:00', '2017-09-25 22:23:22'),
(2, 'Kho số 2', 1, 1, '2016-05-23 00:00:00', '2017-09-25 22:23:28');

-- --------------------------------------------------------

--
-- Table structure for table `cms_suppliers`
--

CREATE TABLE IF NOT EXISTS `cms_suppliers` (
  `ID` int(10) unsigned NOT NULL,
  `supplier_code` varchar(10) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `supplier_phone` varchar(30) NOT NULL,
  `supplier_email` varchar(150) NOT NULL,
  `supplier_addr` varchar(255) NOT NULL,
  `tax_code` varchar(255) NOT NULL,
  `notes` text NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `user_upd` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cms_templates`
--

CREATE TABLE IF NOT EXISTS `cms_templates` (
  `id` int(5) NOT NULL,
  `type` int(5) NOT NULL DEFAULT '0',
  `name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `content` text CHARACTER SET utf8 NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `user_upd` int(11) DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cms_templates`
--

INSERT INTO `cms_templates` (`id`, `type`, `name`, `content`, `created`, `updated`, `user_upd`) VALUES
(1, 1, 'Hóa đơn bán hàng (Pos)', '<div>Cong ty</div>\n\n<div style="text-align:center"><strong>H&Oacute;A ĐƠN B&Aacute;N H&Agrave;NG</strong><br />\n<strong>{Ma_Don_Hang}</strong></div>\n\n<div style="text-align:center">&nbsp;</div>\n\n<div>\n<p><strong>Ng&agrave;y b&aacute;n:</strong> {Ngay_Xuat}<br />\n<strong>Kh&aacute;ch h&agrave;ng:</strong> {Khach_Hang}<br />\n<strong>Địa Chỉ:</strong> {DC_Khach_Hang}<br />\n<strong>ĐT: </strong>{DT_Khach_Hang}</p>\n</div>\n\n<div>{Chi_Tiet_San_Pham}</div>\n\n<div>&nbsp;</div>\n\n<table style="width:100%">\n	<tbody>\n		<tr>\n			<td style="text-align:right">Tổng tiền h&agrave;ng:</td>\n			<td style="text-align:right">{Tong_Tien_Hang}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Giảm gi&aacute;:</td>\n			<td style="text-align:right">{Chiec_Khau}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Đặt cọc</td>\n			<td style="text-align:right">{Khach_Dua}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Tổng thanh to&aacute;n:</td>\n			<td style="text-align:right"><strong>{Con_No}</strong></td>\n		</tr>\n	</tbody>\n</table>\n\n<p style="text-align:center">Số tiền bằng chữ: {So_Tien_Bang_Chu}&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</p>\n\n<p style="text-align:center">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<strong>NGƯỜI B&Aacute;N H&Agrave;NG</strong></p>\n\n<p>&nbsp;</p>\n\n<p style="text-align:right"><strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </strong></p>\n\n<p style="text-align:right"><strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; {Ten_Nhan_Vien}&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</strong></p>\n', NULL, '2020-04-16 10:15:52', 2),
(2, 2, 'Hóa đơn bán hàng (order)', '<div style="text-align:center"><strong>H&Oacute;A ĐƠN B&Aacute;N H&Agrave;NG</strong><br />\n<strong>{Ma_Don_Hang}</strong></div>\n\n<div style="text-align:center">&nbsp;</div>\n\n<div>\n<p><strong>Ng&agrave;y b&aacute;n:</strong> {Ngay_Xuat}<br />\n<strong>Kh&aacute;ch h&agrave;ng:</strong> {Khach_Hang}<br />\n<strong>Địa Chỉ:</strong> {DC_Khach_Hang}<br />\n<strong>ĐT: </strong>{DT_Khach_Hang}</p>\n</div>\n\n<div>{Chi_Tiet_San_Pham}</div>\n\n<div>&nbsp;</div>\n\n<table style="width:100%">\n	<tbody>\n		<tr>\n			<td style="text-align:right">Tổng tiền h&agrave;ng:</td>\n			<td style="text-align:right">{Tong_Tien_Hang}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Giảm gi&aacute;:</td>\n			<td style="text-align:right">{Chiec_Khau}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Đặt cọc</td>\n			<td style="text-align:right">{Khach_Dua}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Tổng thanh to&aacute;n:</td>\n			<td style="text-align:right"><strong>{Con_No}</strong></td>\n		</tr>\n	</tbody>\n</table>\n\n<p style="text-align:center">Số tiền bằng chữ: {So_Tien_Bang_Chu}&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</p>\n\n<p style="text-align:right">&nbsp;<strong>NGƯỜI B&Aacute;N H&Agrave;NG</strong></p>\n\n<p>&nbsp;</p>\n\n<p style="text-align:right"><strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </strong></p>\n\n<p style="text-align:right"><strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; {Ten_Nhan_Vien}&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</strong></p>\n', NULL, '2020-04-16 09:35:11', 2),
(3, 3, 'Hóa đơn phiếu nhập', '<div style="text-align:center"><strong>H&Oacute;A ĐƠN B&Aacute;N H&Agrave;NG</strong><br />\n<strong>{Ma_Don_Hang}</strong></div>\n\n<div style="text-align:center">&nbsp;</div>\n\n<div>\n<p><strong>Ng&agrave;y b&aacute;n:</strong> {Ngay_Xuat}<br />\n<strong>Kh&aacute;ch h&agrave;ng:</strong> {Khach_Hang}<br />\n<strong>Địa Chỉ:</strong> {DC_Khach_Hang}<br />\n<strong>ĐT: </strong>{DT_Khach_Hang}</p>\n</div>\n\n<div>{Chi_Tiet_San_Pham}</div>\n\n<div>&nbsp;</div>\n\n<table style="width:100%">\n	<tbody>\n		<tr>\n			<td style="text-align:right">Tổng tiền h&agrave;ng:</td>\n			<td style="text-align:right">{Tong_Tien_Hang}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Giảm gi&aacute;:</td>\n			<td style="text-align:right">{Chiec_Khau}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Đặt cọc</td>\n			<td style="text-align:right">{Khach_Dua}</td>\n		</tr>\n		<tr>\n			<td style="text-align:right">Tổng thanh to&aacute;n:</td>\n			<td style="text-align:right"><strong>{Con_No}</strong></td>\n		</tr>\n	</tbody>\n</table>\n\n<p style="text-align:center">Số tiền bằng chữ: {So_Tien_Bang_Chu}&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</p>\n\n<p style="text-align:right">&nbsp;<strong>NGƯỜI B&Aacute;N H&Agrave;NG</strong></p>\n\n<p>&nbsp;</p>\n\n<p style="text-align:right"><strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </strong></p>\n\n<p style="text-align:right"><strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; {Ten_Nhan_Vien}&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</strong></p>\n', NULL, '2020-04-16 09:35:00', 2);

-- --------------------------------------------------------

--
-- Table structure for table `cms_thuchi`
--

CREATE TABLE IF NOT EXISTS `cms_thuchi` (
  `ID` int(10) unsigned NOT NULL,
  `thuchi_code` varchar(9) NOT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `thuchi_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_init` int(11) NOT NULL DEFAULT '0',
  `notes` varchar(255) NOT NULL,
  `hinhthuc` tinyint(4) NOT NULL DEFAULT '0',
  `loaiphieu` varchar(50) NOT NULL,
  `tongtien` int(13) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM AUTO_INCREMENT=66 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `cms_users`
--

CREATE TABLE IF NOT EXISTS `cms_users` (
  `id` int(10) unsigned NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `salt` varchar(255) NOT NULL,
  `email` varchar(120) NOT NULL,
  `display_name` varchar(120) NOT NULL,
  `user_status` tinyint(4) NOT NULL DEFAULT '0',
  `group_id` int(11) NOT NULL DEFAULT '0',
  `store_id` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logined` datetime(1) NOT NULL,
  `ip_logged` varchar(255) NOT NULL,
  `recode` varchar(255) NOT NULL,
  `code_time_out` varchar(255) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_users`
--

INSERT INTO `cms_users` (`id`, `username`, `password`, `salt`, `email`, `display_name`, `user_status`, `group_id`, `store_id`, `created`, `updated`, `logined`, `ip_logged`, `recode`, `code_time_out`) VALUES
(2, 'admin', 'ccd6971f31d47521b505a5fde61c5a7a', 'ZM3NvoJrZaEEWTrFW3BrA13SWoHGA3kXFmYWJinS97xWWkSaZGnRDKZNI8826GXgP8VTi', 'admin@gmail.com', 'Trưởng phòng KD', 1, 1, 1, '2017-09-25 23:01:53', '2019-05-05 14:14:48', '2020-04-16 13:56:09.0', '::1', '', ''),
(5, 'ketoan', '8e1b215103418cddfe0d7586cf438364', 'QHt)IG)wmp*4a7Y@6UwE^UIbDHLvzkzrr0zd!#l2wqBzdVXy$bDkQIbH)tOCqQz!cLaZo', 'nhanvien@gmail.com', 'Kế toán kho', 1, 2, 1, '2017-11-04 11:35:49', '2019-05-05 14:14:27', '2017-11-04 11:36:09.0', '156.67.222.9', '', ''),
(11, 'nv005', '6d6eea92fe77c0b1b96911f115645723', 'NpdGMP9@mDXROR@QrsaG&!9e4RjliG5TNgnTulZ2E#ESm!aRLDJ1E0&ayU8OWpsVNpXW!', 'nv05@gmail.com', 'nv005', 0, 3, 1, '2020-01-09 20:46:49', '2020-04-16 09:14:41', '0000-00-00 00:00:00.0', '', '', ''),
(12, 'Nv ban hang', '7517ccaac2bee419bf5d625461ae1e8f', 'r(nyJH&oI8sGu%F*V*64V6@lTjrCS53RQyPPyCOEJ4XyKhX^M#%9ph#d^OQ7#t3iMu80g', 'nvbh00@gmail.com', 'nv007', 1, 3, 1, '2020-04-16 09:53:05', '2020-04-16 09:55:01', '0000-00-00 00:00:00.0', '', '', ''),
(13, 'nv008', '37fe38cde065402af5f399da6679dc4c', 'Qlc9gJPpGB5^HYsG!)d6aknUSBFdlKwcw!pCtP5XBnL6C(kQX%Qogq93PIA@dQ!w^s1LP', 'nv008@gmail.com', 'nv008', 1, 3, 1, '2020-04-16 09:55:29', '2020-04-16 09:55:33', '2020-04-16 09:55:49.0', '::1', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `cms_users_group`
--

CREATE TABLE IF NOT EXISTS `cms_users_group` (
  `id` int(10) unsigned NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_permission` varchar(255) NOT NULL,
  `group_registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `group_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cms_users_group`
--

INSERT INTO `cms_users_group` (`id`, `group_name`, `group_permission`, `group_registered`, `group_updated`) VALUES
(3, 'Nhân viên', '["1","2","3","4","5","6","7","8","9","10"]', '2017-09-08 19:20:45', '0000-00-00 00:00:00'),
(1, 'Admin', '["1","2","3","4","5","6","7","8","9","10"]', '2016-01-22 02:58:58', '2016-06-15 21:42:04'),
(2, 'Quản lý', '["1","2","3","4","5","6","7","8","9","10"]', '2016-01-22 03:00:40', '2016-06-15 21:42:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cms_customers`
--
ALTER TABLE `cms_customers`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_input`
--
ALTER TABLE `cms_input`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_inventory`
--
ALTER TABLE `cms_inventory`
  ADD PRIMARY KEY (`store_id`,`product_id`);

--
-- Indexes for table `cms_orders`
--
ALTER TABLE `cms_orders`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_permissions`
--
ALTER TABLE `cms_permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_picture`
--
ALTER TABLE `cms_picture`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_products`
--
ALTER TABLE `cms_products`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_products_group`
--
ALTER TABLE `cms_products_group`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_products_manufacture`
--
ALTER TABLE `cms_products_manufacture`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_report`
--
ALTER TABLE `cms_report`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_stores`
--
ALTER TABLE `cms_stores`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_suppliers`
--
ALTER TABLE `cms_suppliers`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_templates`
--
ALTER TABLE `cms_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_thuchi`
--
ALTER TABLE `cms_thuchi`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cms_users`
--
ALTER TABLE `cms_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_users_group`
--
ALTER TABLE `cms_users_group`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cms_customers`
--
ALTER TABLE `cms_customers`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=49;
--
-- AUTO_INCREMENT for table `cms_input`
--
ALTER TABLE `cms_input`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=45;
--
-- AUTO_INCREMENT for table `cms_orders`
--
ALTER TABLE `cms_orders`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=350;
--
-- AUTO_INCREMENT for table `cms_permissions`
--
ALTER TABLE `cms_permissions`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `cms_picture`
--
ALTER TABLE `cms_picture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `cms_products`
--
ALTER TABLE `cms_products`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=176;
--
-- AUTO_INCREMENT for table `cms_products_group`
--
ALTER TABLE `cms_products_group`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=34;
--
-- AUTO_INCREMENT for table `cms_products_manufacture`
--
ALTER TABLE `cms_products_manufacture`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `cms_report`
--
ALTER TABLE `cms_report`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=613;
--
-- AUTO_INCREMENT for table `cms_stores`
--
ALTER TABLE `cms_stores`
  MODIFY `ID` int(5) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `cms_suppliers`
--
ALTER TABLE `cms_suppliers`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=25;
--
-- AUTO_INCREMENT for table `cms_templates`
--
ALTER TABLE `cms_templates`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `cms_thuchi`
--
ALTER TABLE `cms_thuchi`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=66;
--
-- AUTO_INCREMENT for table `cms_users`
--
ALTER TABLE `cms_users`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT for table `cms_users_group`
--
ALTER TABLE `cms_users_group`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
