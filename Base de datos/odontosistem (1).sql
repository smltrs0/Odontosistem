-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 10, 2020 at 08:11 PM
-- Server version: 5.7.24
-- PHP Version: 7.2.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `odontosistem`
--

-- --------------------------------------------------------

--
-- Table structure for table `citas`
--

CREATE TABLE `citas` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `citas`
--

INSERT INTO `citas` (`id`, `fecha`, `hora`, `paciente_id`, `updated_at`, `created_at`) VALUES
(6, '2020-08-10', '23:00:00', 9, '2020-08-24 00:00:00', '2020-08-11 00:00:00'),
(10, '2020-08-10', '16:04:00', 16, '2020-08-09 17:00:01', '2020-08-09 17:00:01'),
(11, '2020-08-10', '23:08:00', 2, '2020-08-09 23:05:10', '2020-08-09 23:05:10');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pacientes`
--

CREATE TABLE `pacientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `second_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otros` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `second_last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dni` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sex` tinyint(1) DEFAULT NULL,
  `height` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `antecedentes` json DEFAULT NULL,
  `habitos` json DEFAULT NULL,
  `alergias` json DEFAULT NULL,
  `medical_history` json DEFAULT NULL,
  `procedures` json DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `registered_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pacientes`
--

INSERT INTO `pacientes` (`id`, `name`, `second_name`, `last_name`, `otros`, `second_last_name`, `dni`, `email`, `birth_date`, `address`, `phone`, `sex`, `height`, `weight`, `antecedentes`, `habitos`, `alergias`, `medical_history`, `procedures`, `user_id`, `registered_by`, `created_at`, `updated_at`) VALUES
(1, 'angelo', 'Rafael', 'Amaro', NULL, 'Trujillo', '252525', NULL, '2020-06-04', 'marhuanta', '11111111111111', 1, NULL, NULL, 'null', 'null', 'null', NULL, NULL, NULL, 1, NULL, '2020-06-07 20:54:56'),
(2, 'Samuel', 'emmanuel', 'Trias', NULL, 'Santamaria', '24186725', NULL, '1994-12-10', 'San José', '04163891799', 1, NULL, NULL, 'null', 'null', 'null', NULL, NULL, 1, 1, '2020-06-05 12:05:45', '2020-08-10 03:35:35'),
(8, 'Saul', NULL, 'Yanave', NULL, 'Guilarte', '25914064', NULL, '1997-10-31', 'Marhuanta', '04163891799', 1, NULL, NULL, 'null', 'null', 'null', NULL, NULL, NULL, 1, '2020-06-05 23:49:54', '2020-06-26 13:16:31'),
(9, 'ivan', NULL, 'ascanio', NULL, NULL, '25252525', 'ivan@test.com', '1995-07-01', 'Su casa', '21', 1, NULL, NULL, 'null', 'null', 'null', NULL, NULL, NULL, 1, '2020-07-27 04:30:00', '2020-08-09 15:02:29'),
(16, 'Loidy', NULL, 'Torrealba', NULL, NULL, '25914065', 'test@test.com', '2020-08-11', 'San José', '11111111111111', 0, NULL, NULL, '[\"test\", \"\"]', '[\"test\", \"\"]', '[\"test\", \" otra alergia\", \" mariscos\", \"\"]', NULL, '[]', 5, 1, '2020-08-09 21:29:49', '2020-08-10 19:05:31');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`email`, `token`, `created_at`) VALUES
('admin@admin.com', '$2y$10$3b1MP9U0iVKHu74HmWD1Y.myLbJQT6aJy.O44tvxnRwSh0zdn9vO6', '2020-07-03 19:40:59');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'List role', 'role.index', 'A user can list role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(2, 'Show role', 'role.show', 'A user can see role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(3, 'Create role', 'role.create', 'A user can create role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(4, 'Edit role', 'role.edit', 'A user can edit role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(5, 'Destroy role', 'role.destroy', 'A user can destroy role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(6, 'List user', 'user.index', 'A user can list user', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(7, 'Show user', 'user.show', 'A user can see user', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(8, 'Edit user', 'user.edit', 'A user can edit user', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(9, 'Destroy user', 'user.destroy', 'A user can destroy user', '2020-06-05 02:33:22', '2020-06-05 02:33:22');

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `full-access` enum('yes','no') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `full-access`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin', 'Administrator', 'yes', '2020-06-05 02:33:21', '2020-06-05 02:33:21'),
(2, 'Invitado', 'guest', 'sin ningún tipo de control ni acceso', 'no', '2020-06-05 18:18:00', '2020-07-13 19:56:11');

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_user`
--

INSERT INTO `role_user` (`id`, `role_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2020-06-05 02:33:21', '2020-06-05 02:33:21'),
(2, 2, 3, '2020-08-08 17:20:00', '2020-08-08 17:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@admin.com', NULL, '$2y$10$gVcSaZpcdcnVhGGbU3M0rukwY8OD.E9z45m73CQ3wK2Vn9nq/jTBO', 'XI27rjVHJkacWH8WVQoIxQJaGaIzt9gLzLysOJt7ELiKGtRAmwCiiX5CFXIz', '2020-06-05 02:33:21', '2020-06-05 02:33:21'),
(3, 'lisbeth santmaria', 'lisbethsantamaria2009@hotmail.com', NULL, '$2y$10$lZeb6zWrDWBU/P1AtVfEaOK0ugg2Wf4YO3u8J4xrVM/tqM07ydLzK', NULL, '2020-08-08 17:16:36', '2020-08-08 17:16:36'),
(5, 'Loidy Torrealba', 'test@test.com', NULL, '$2y$10$MKr1A1RHcpZiO5oh8Fek8eu//qvJCxMQu9dtmamiTkPQp1ifmAnZK', NULL, '2020-08-08 21:32:36', '2020-08-09 02:54:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `pacientes_user_id_foreign` (`user_id`),
  ADD KEY `pacientes_registered_by_foreign` (`registered_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`),
  ADD UNIQUE KEY `permissions_slug_unique` (`slug`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`),
  ADD KEY `permission_role_permission_id_foreign` (`permission_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_user_role_id_foreign` (`role_id`),
  ADD KEY `role_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `permission_role`
--
ALTER TABLE `permission_role`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `role_user`
--
ALTER TABLE `role_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `FK_citas_pacientes` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`);

--
-- Constraints for table `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `pacientes_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pacientes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
